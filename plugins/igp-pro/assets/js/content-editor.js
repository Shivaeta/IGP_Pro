(function () {
	'use strict';

	var root = document.getElementById('igp-pro-content-editor');
	var config = window.igpProContentEditor || {};

	if (!root || !config.ajaxUrl) {
		return;
	}

	var state = {
		loading: true,
		posts: [],
		blocks: [],
		selectedPostId: '',
		loadedPost: null,
		graph: { version: 'v1', sections: [] },
		metaDescription: '',
		status: '',
		statusType: '',
		dirty: false,
		collapsed: {},
		contentSource: '',
		postSearch: '',
		searchTimer: null,
		searchingPosts: false,
		relationshipSearch: {},
		relationshipSearching: {}
	};

	function t(key, fallback) {
		return (config.i18n && config.i18n[key]) || fallback || key;
	}

	function request(action, data) {
		var params = new URLSearchParams();
		params.append('action', action);
		params.append('nonce', config.nonce || '');
		Object.keys(data || {}).forEach(function (key) {
			params.append(key, data[key]);
		});

		return fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: params.toString()
		}).then(function (response) {
			return response.json().then(function (payload) {
				if (!response.ok || !payload.success) {
					var message = payload && payload.data && payload.data.message ? payload.data.message : 'Request failed.';
					throw new Error(message);
				}
				return payload.data;
			});
		});
	}

	function setStatus(message, type) {
		state.status = message || '';
		state.statusType = type || '';
		render();
	}

	function markDirty() {
		state.dirty = true;
	}

	window.addEventListener('beforeunload', function (event) {
		if (!state.dirty) {
			return;
		}
		event.preventDefault();
		event.returnValue = t('unsavedChanges', 'You have unsaved IGP Pro content graph changes.');
	});

	function el(tag, attrs, children) {
		var node = document.createElement(tag);
		attrs = attrs || {};
		Object.keys(attrs).forEach(function (key) {
			var value = attrs[key];
			if (key === 'className') {
				node.className = value || '';
			} else if (key === 'text') {
				node.textContent = value === null || value === undefined ? '' : String(value);
			} else if (key === 'html') {
				node.innerHTML = value || '';
			} else if (key.indexOf('on') === 0 && typeof value === 'function') {
				node.addEventListener(key.substring(2).toLowerCase(), value);
			} else if (key === 'value') {
				node.value = value === null || value === undefined ? '' : String(value);
			} else if (key === 'checked') {
				node.checked = !!value;
			} else if (key === 'selected') {
				node.selected = !!value;
			} else if (value !== false && value !== null && value !== undefined) {
				node.setAttribute(key, value === true ? key : String(value));
			}
		});
		(children || []).forEach(function (child) {
			if (child === null || child === undefined) {
				return;
			}
			node.appendChild(typeof child === 'string' ? document.createTextNode(child) : child);
		});
		return node;
	}

	function labelize(name) {
		return String(name || '').replace(/_/g, ' ').replace(/-/g, ' ').replace(/\b\w/g, function (match) {
			return match.toUpperCase();
		});
	}

	function clone(value) {
		return JSON.parse(JSON.stringify(value === undefined ? null : value));
	}

	function uniqueId(prefix) {
		return (prefix || 'item') + '_' + Date.now() + '_' + Math.floor(Math.random() * 100000);
	}

	function getBlock(blockId) {
		return state.blocks.find(function (block) { return block.id === blockId; }) || null;
	}

	function ensureGraph() {
		if (!state.graph || typeof state.graph !== 'object') {
			state.graph = { version: 'v1', sections: [] };
		}
		if (!Array.isArray(state.graph.sections)) {
			state.graph.sections = [];
		}
		state.graph.version = state.graph.version || 'v1';
	}

	function createDefaultData(block) {
		return clone(block && block.defaults ? block.defaults : {});
	}

	function createSection(blockId) {
		var block = getBlock(blockId);
		if (!block) {
			return null;
		}
		var section = {
			id: uniqueId('section'),
			block_id: block.id,
			data: createDefaultData(block)
		};
		if (block.id === 'section') {
			section.children = [];
		}
		return section;
	}

	function pathKey(path) {
		return (path || []).join('.');
	}

	function getSectionsAt(parentPath) {
		ensureGraph();
		if (!parentPath || !parentPath.length) {
			return state.graph.sections;
		}
		var parent = getSection(parentPath);
		if (!parent) {
			return [];
		}
		if (!Array.isArray(parent.children)) {
			parent.children = [];
		}
		return parent.children;
	}

	function getSection(path) {
		ensureGraph();
		var sections = state.graph.sections;
		var section = null;
		for (var i = 0; i < path.length; i += 1) {
			section = sections[path[i]];
			if (!section) {
				return null;
			}
			sections = Array.isArray(section.children) ? section.children : [];
		}
		return section;
	}

	function getPathValue(object, path) {
		var value = object;
		for (var i = 0; i < path.length; i += 1) {
			if (value === null || value === undefined) {
				return undefined;
			}
			value = value[path[i]];
		}
		return value;
	}

	function setPathValue(object, path, value) {
		var target = object;
		for (var i = 0; i < path.length - 1; i += 1) {
			var key = path[i];
			if (!target[key] || typeof target[key] !== 'object') {
				target[key] = typeof path[i + 1] === 'number' ? [] : {};
			}
			target = target[key];
		}
		target[path[path.length - 1]] = value;
	}

	function updateSectionData(sectionPath, dataPath, value, options) {
		var section = getSection(sectionPath);
		if (!section) {
			return;
		}
		if (!section.data || typeof section.data !== 'object') {
			section.data = {};
		}
		setPathValue(section.data, dataPath, value);
		markDirty();
		if (options && options.render) {
			render();
		}
	}

	function normalizeExpectedPostType(value) {
		value = String(value || '').trim();
		if (value === 'tour') {
			return ['tour', 'igp_tour'];
		}
		if (value === 'destination') {
			return ['destination', 'igp_destination'];
		}
		if (value === 'page') {
			return ['page'];
		}
		return [];
	}

	function postTypeMatches(post, expected) {
		var allowed = normalizeExpectedPostType(expected);
		if (!allowed.length || expected === 'any') {
			return true;
		}
		return allowed.indexOf(String(post.post_type || '')) !== -1;
	}

	function formatPostOption(post) {
		return '[' + post.post_type + '] ' + post.title + ' #' + post.id + (post.status ? ' · ' + post.status : '');
	}

	function mergePosts(posts) {
		var byId = {};
		state.posts.forEach(function (post) { byId[String(post.id)] = post; });
		(posts || []).forEach(function (post) { byId[String(post.id)] = post; });
		state.posts = Object.keys(byId).map(function (id) { return byId[id]; }).sort(function (a, b) {
			return String(formatPostOption(a)).localeCompare(String(formatPostOption(b)));
		});
	}

	function searchPosts(term, limit) {
		return request('igp_pro_search_content_editor_posts', { search: term || '', limit: limit || 120 }).then(function (data) {
			mergePosts(data.posts || []);
			return data.posts || [];
		});
	}

	function isMediaUrlField(label, field, path) {
		var type = field.type || 'string';
		if (type !== 'string' && type !== 'url') {
			return false;
		}
		var name = String(label || '').toLowerCase();
		var pathText = (path || []).join('.').toLowerCase();
		if (field.media === true || field.format === 'image-url') {
			return true;
		}
		if (/image|media|photo|avatar|logo|thumbnail|background/.test(name)) {
			return true;
		}
		if (name === 'url' && /(^|\.)(images|logos)(\.|$)/.test(pathText)) {
			return true;
		}
		return false;
	}

	function renderMediaUrlInput(label, field, value, onChange) {
		var wrapper = el('div', { className: 'igp-pro-field igp-pro-field--media-url' });
		wrapper.appendChild(el('label', {}, [document.createTextNode(field.label || labelize(label)), field.required ? el('span', { className: 'igp-pro-required', text: ' *' }) : null]));
		var input = el('input', {
			type: 'url',
			value: value === undefined || value === null ? '' : value,
			placeholder: 'https://example.com/image.jpg or /wp-content/uploads/image.jpg',
			onInput: function (event) { onChange(event.target.value); }
		});
		var mediaButton = el('button', {
			type: 'button',
			className: 'button',
			text: 'Media',
			onClick: function () {
				if (!window.wp || !window.wp.media) {
					return;
				}
				var frame = window.wp.media({ title: t('chooseImage', 'Choose image'), button: { text: t('useImage', 'Use this image') }, multiple: false });
				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					onChange(attachment.url || '', { render: true });
				});
				frame.open();
			}
		});
		wrapper.appendChild(el('div', { className: 'igp-pro-image-control' }, [input, mediaButton]));
		wrapper.appendChild(el('small', { text: 'Use the media picker for uploaded WordPress images. Add/edit alt text in the adjacent Alt field when available.' }));
		if (value) {
			wrapper.appendChild(el('img', { className: 'igp-pro-image-preview', src: value, alt: '' }));
		}
		return wrapper;
	}

	function renderImageObject(label, field, value, onChange) {
		var wrapper = el('fieldset', { className: 'igp-pro-fieldset igp-pro-image-object' }, [el('legend', { text: field.label || labelize(label) })]);
		var image = value && typeof value === 'object' && !Array.isArray(value) ? value : { url: value || '', alt: '' };
		var currentImage = Object.assign({ attachment_id: 0, url: '', alt: '', caption: '', pending: false, prompt: '' }, image);
		wrapper.appendChild(renderMediaUrlInput('url', { label: 'Image URL' }, currentImage.url || '', function (next, options) {
			currentImage.url = next;
			currentImage.pending = false;
			onChange(Object.assign({}, currentImage), options || {});
		}));
		wrapper.appendChild(renderInput('alt', { type: 'string', label: 'Alt text' }, currentImage.alt || '', function (next) {
			currentImage.alt = next;
			onChange(Object.assign({}, currentImage));
		}, []));
		wrapper.appendChild(renderInput('caption', { type: 'string', label: 'Caption' }, currentImage.caption || '', function (next) {
			currentImage.caption = next;
			onChange(Object.assign({}, currentImage));
		}, []));
		wrapper.appendChild(renderInput('pending', { type: 'boolean', label: 'Pending media requirement' }, !!currentImage.pending, function (next) {
			currentImage.pending = !!next;
			onChange(Object.assign({}, currentImage));
		}, []));
		if (currentImage.pending) {
			wrapper.appendChild(renderInput('prompt', { type: 'text', label: 'Media prompt' }, currentImage.prompt || '', function (next) {
				currentImage.prompt = next;
				onChange(Object.assign({}, currentImage));
			}, []));
		}
		return wrapper;
	}

	function renderRelationshipPicker(label, field, value, onChange, key) {
		var selected = Array.isArray(value) ? value.slice() : (value ? [parseInt(value, 10)] : []);
		selected = selected.map(function (id) { return parseInt(id, 10); }).filter(function (id, index, arr) { return id > 0 && arr.indexOf(id) === index; });
		var expected = field.post_type || 'any';
		var wrapper = el('fieldset', { className: 'igp-pro-fieldset igp-pro-relationship-picker' }, [el('legend', { text: field.label || labelize(label) })]);
		var searchKey = key || label;
		var term = state.relationshipSearch[searchKey] || '';
		var filter = term.trim().toLowerCase();
		var candidates = state.posts.filter(function (post) {
			if (!postTypeMatches(post, expected)) {
				return false;
			}
			if (!filter) {
				return true;
			}
			return formatPostOption(post).toLowerCase().indexOf(filter) !== -1;
		});
		var selectedMap = {};
		selected.forEach(function (id) { selectedMap[String(id)] = true; });

		wrapper.appendChild(el('small', { text: 'Select valid posts. The save service rejects wrong post types and deleted IDs.' }));
		wrapper.appendChild(el('div', { className: 'igp-pro-relationship-search' }, [
			el('input', {
				type: 'search',
				value: term,
				placeholder: 'Search ' + (expected === 'any' ? 'posts' : expected + ' posts') + ' by title or #ID',
				onInput: function (event) {
					state.relationshipSearch[searchKey] = event.target.value;
					render();
				}
			}),
			el('button', {
				type: 'button',
				className: 'button',
				text: state.relationshipSearching[searchKey] ? 'Searching…' : 'Search more',
				onClick: function () {
					state.relationshipSearching[searchKey] = true;
					searchPosts(state.relationshipSearch[searchKey] || '', 120).then(function () {
						state.relationshipSearching[searchKey] = false;
						render();
					}).catch(function (error) {
						state.relationshipSearching[searchKey] = false;
						setStatus(error.message || 'Relationship search failed.', 'error');
					});
				}
			})
		]));

		var selectedPosts = selected.map(function (id) {
			return state.posts.find(function (post) { return parseInt(post.id, 10) === id; }) || { id: id, title: 'Unknown/deleted post', post_type: 'unknown', status: 'missing' };
		});
		if (selectedPosts.length) {
			wrapper.appendChild(el('div', { className: 'igp-pro-selected-relationships' }, selectedPosts.map(function (post) {
				var invalid = !postTypeMatches(post, expected) || post.status === 'missing';
				return el('span', { className: 'igp-pro-relation-chip' + (invalid ? ' is-invalid' : '') }, [
					document.createTextNode(formatPostOption(post)),
					el('button', {
						type: 'button',
						className: 'button-link-delete',
						text: ' ×',
						onClick: function () {
							selected = selected.filter(function (id) { return parseInt(id, 10) !== parseInt(post.id, 10); });
							onChange(selected, { render: true });
						}
					})
				]);
			})));
		}

		var list = el('div', { className: 'igp-pro-relationship-list' });
		candidates.slice(0, 60).forEach(function (post) {
			var id = parseInt(post.id, 10);
			var checkbox = el('input', {
				type: 'checkbox',
				checked: !!selectedMap[String(id)],
				onChange: function (event) {
					var next = selected.slice();
					if (event.target.checked && next.indexOf(id) === -1) {
						next.push(id);
					} else if (!event.target.checked) {
						next = next.filter(function (selectedId) { return selectedId !== id; });
					}
					onChange(next, { render: true });
				}
			});
			list.appendChild(el('label', { className: 'igp-pro-relationship-option' }, [checkbox, document.createTextNode(' ' + formatPostOption(post))]));
		});
		if (!candidates.length) {
			list.appendChild(el('p', { className: 'description', text: 'No matching posts loaded. Search by title or #ID.' }));
		}
		wrapper.appendChild(list);
		return wrapper;
	}

	function renderInput(label, field, value, onChange, path) {
		var type = field.type || 'string';
		var wrapper = el('div', { className: 'igp-pro-field' });
		var required = field.required ? el('span', { className: 'igp-pro-required', text: ' *' }) : null;
		wrapper.appendChild(el('label', {}, [document.createTextNode(field.label || labelize(label)), required]));

		if (isMediaUrlField(label, field, path || [])) {
			return renderMediaUrlInput(label, field, value, onChange);
		}
		if (type === 'image') {
			return renderImageObject(label, field, value, onChange);
		}
		if (type === 'text') {
			wrapper.appendChild(el('textarea', { rows: 4, value: value || '', onInput: function (event) { onChange(event.target.value); } }));
			return wrapper;
		}
		if (type === 'number') {
			wrapper.appendChild(el('input', { type: 'number', min: field.min, max: field.max, value: value === undefined || value === null ? '' : value, onInput: function (event) { onChange(event.target.value === '' ? '' : Number(event.target.value)); } }));
			return wrapper;
		}
		if (type === 'boolean') {
			var checkbox = el('input', { type: 'checkbox', checked: !!value, onChange: function (event) { onChange(!!event.target.checked); } });
			wrapper.appendChild(el('label', { className: 'igp-pro-checkbox-label' }, [checkbox, document.createTextNode(' Enabled')]));
			return wrapper;
		}
		if (type === 'enum') {
			var select = el('select', { onChange: function (event) { onChange(event.target.value); } });
			(field.values || []).forEach(function (option) { select.appendChild(el('option', { value: option, text: labelize(option) })); });
			select.value = value || field.default || ((field.values || [])[0] || '');
			wrapper.appendChild(select);
			return wrapper;
		}
		if (type === 'relationship') {
			return renderRelationshipPicker(label, field, value, onChange, (path || []).join('.'));
		}
		wrapper.appendChild(el('input', { type: (String(label).toLowerCase().indexOf('url') !== -1 ? 'url' : 'text'), value: value === undefined || value === null ? '' : value, onInput: function (event) { onChange(event.target.value); } }));
		return wrapper;
	}

	function renderFields(container, fields, data, sectionPath, basePath) {
		Object.keys(fields || {}).forEach(function (fieldName) {
			var field = fields[fieldName] || {};
			var type = field.type || 'string';
			var path = basePath.concat([fieldName]);
			var value = getPathValue(data, path);
			if (value === undefined && field.default !== undefined) {
				value = clone(field.default);
			}
			if (type === 'object') {
				var fieldset = el('fieldset', { className: 'igp-pro-fieldset' }, [el('legend', { text: field.label || labelize(fieldName) })]);
				renderFields(fieldset, field.fields || {}, data, sectionPath, path);
				container.appendChild(fieldset);
				return;
			}
			if (type === 'repeater' || type === 'array') {
				container.appendChild(renderRepeater(fieldName, field, Array.isArray(value) ? value : [], sectionPath, path, data));
				return;
			}
			container.appendChild(renderInput(fieldName, field, value, function (next, options) {
				updateSectionData(sectionPath, path, next, options || {});
			}, path));
		});
	}

	function createDefaultRepeaterItem(itemFields) {
		var nextItem = {};
		Object.keys(itemFields || {}).forEach(function (childName) {
			var child = itemFields[childName] || {};
			if (child.default !== undefined) {
				nextItem[childName] = clone(child.default);
			} else if (child.type === 'boolean') {
				nextItem[childName] = false;
			} else if (child.type === 'number') {
				nextItem[childName] = 0;
			} else if (child.type === 'repeater' || child.type === 'array' || child.type === 'relationship') {
				nextItem[childName] = [];
			} else if (child.type === 'object') {
				nextItem[childName] = createDefaultRepeaterItem(child.fields || {});
			} else if (child.type === 'image') {
				nextItem[childName] = { url: '', alt: '' };
			} else {
				nextItem[childName] = '';
			}
		});
		nextItem.id = nextItem.id || uniqueId('item');
		return nextItem;
	}

	function renderRepeater(fieldName, field, items, sectionPath, path, data) {
		var wrapper = el('fieldset', { className: 'igp-pro-fieldset igp-pro-repeater' }, [el('legend', { text: field.label || labelize(fieldName) })]);
		var itemFields = field.fields || null;
		items = items.slice();

		if (!itemFields || !Object.keys(itemFields).length) {
			items.forEach(function (item, itemIndex) {
				wrapper.appendChild(el('div', { className: 'igp-pro-repeater-scalar' }, [
					el('input', { type: 'text', value: item === undefined || item === null ? '' : item, onInput: function (event) { items[itemIndex] = event.target.value; updateSectionData(sectionPath, path, items); } }),
					el('button', { type: 'button', className: 'button button-small', text: 'Up', onClick: function () { if (itemIndex > 0) { var moved = items.splice(itemIndex, 1)[0]; items.splice(itemIndex - 1, 0, moved); updateSectionData(sectionPath, path, items, { render: true }); } } }),
					el('button', { type: 'button', className: 'button button-small', text: 'Down', onClick: function () { if (itemIndex < items.length - 1) { var moved = items.splice(itemIndex, 1)[0]; items.splice(itemIndex + 1, 0, moved); updateSectionData(sectionPath, path, items, { render: true }); } } }),
					el('button', { type: 'button', className: 'button button-small button-link-delete', text: 'Remove', onClick: function () { items.splice(itemIndex, 1); updateSectionData(sectionPath, path, items, { render: true }); } })
				]));
			});
			wrapper.appendChild(el('button', { type: 'button', className: 'button', text: 'Add item', onClick: function () { items.push(''); updateSectionData(sectionPath, path, items, { render: true }); } }));
			return wrapper;
		}

		items.forEach(function (item, itemIndex) {
			if (!item || typeof item !== 'object' || Array.isArray(item)) {
				items[itemIndex] = createDefaultRepeaterItem(itemFields);
			}
			var itemBox = el('div', { className: 'igp-pro-repeater-item' });
			itemBox.appendChild(el('div', { className: 'igp-pro-repeater-header' }, [
				el('strong', { text: labelize(fieldName) + ' #' + (itemIndex + 1) }),
				el('div', { className: 'igp-pro-repeater-actions' }, [
					el('button', { type: 'button', className: 'button button-small', text: 'Up', onClick: function () { if (itemIndex > 0) { var moved = items.splice(itemIndex, 1)[0]; items.splice(itemIndex - 1, 0, moved); updateSectionData(sectionPath, path, items, { render: true }); } } }),
					el('button', { type: 'button', className: 'button button-small', text: 'Down', onClick: function () { if (itemIndex < items.length - 1) { var moved = items.splice(itemIndex, 1)[0]; items.splice(itemIndex + 1, 0, moved); updateSectionData(sectionPath, path, items, { render: true }); } } }),
					el('button', { type: 'button', className: 'button button-small', text: 'Duplicate', onClick: function () { var copy = clone(items[itemIndex]); copy.id = uniqueId('item'); items.splice(itemIndex + 1, 0, copy); updateSectionData(sectionPath, path, items, { render: true }); } }),
					el('button', { type: 'button', className: 'button button-small button-link-delete', text: 'Remove', onClick: function () { items.splice(itemIndex, 1); updateSectionData(sectionPath, path, items, { render: true }); } })
				])
			]));
			renderFields(itemBox, itemFields, data, sectionPath, path.concat([itemIndex]));
			wrapper.appendChild(itemBox);
		});

		wrapper.appendChild(el('button', { type: 'button', className: 'button', text: 'Add item', onClick: function () { items.push(createDefaultRepeaterItem(itemFields)); updateSectionData(sectionPath, path, items, { render: true }); } }));
		return wrapper;
	}

	function addSection(blockId, parentPath) {
		var section = createSection(blockId);
		if (!section) {
			return;
		}
		getSectionsAt(parentPath || []).push(section);
		markDirty();
		render();
	}

	function removeSection(path) {
		if (!window.confirm(t('confirmDelete', 'Remove this section?'))) {
			return;
		}
		var parentPath = path.slice(0, -1);
		var index = path[path.length - 1];
		getSectionsAt(parentPath).splice(index, 1);
		markDirty();
		render();
	}

	function duplicateSection(path) {
		var parentPath = path.slice(0, -1);
		var index = path[path.length - 1];
		var copy = clone(getSectionsAt(parentPath)[index]);
		copy.id = uniqueId('section');
		getSectionsAt(parentPath).splice(index + 1, 0, copy);
		markDirty();
		render();
	}

	function moveSection(path, direction) {
		var parentPath = path.slice(0, -1);
		var index = path[path.length - 1];
		var sections = getSectionsAt(parentPath);
		var next = index + direction;
		if (next < 0 || next >= sections.length) {
			return;
		}
		var item = sections.splice(index, 1)[0];
		sections.splice(next, 0, item);
		markDirty();
		render();
	}

	function renderSection(section, path, depth) {
		depth = depth || 0;
		var block = getBlock(section.block_id);
		var title = block ? block.title : section.block_id;
		var sectionId = section.id || ('section_' + pathKey(path));
		var isCollapsed = !!state.collapsed[sectionId];
		var wrapper = el('div', { className: 'igp-pro-section igp-pro-section-depth-' + depth + (isCollapsed ? ' is-collapsed' : '') });
		wrapper.appendChild(el('div', { className: 'igp-pro-section-header', onClick: function () { state.collapsed[sectionId] = !state.collapsed[sectionId]; render(); } }, [
			el('div', { className: 'igp-pro-section-title', text: path.map(function (i) { return i + 1; }).join('.') + '. ' + title }),
			el('div', { className: 'igp-pro-section-actions' }, [
				el('button', { type: 'button', className: 'button button-small', text: 'Up', onClick: function (event) { event.stopPropagation(); moveSection(path, -1); } }),
				el('button', { type: 'button', className: 'button button-small', text: 'Down', onClick: function (event) { event.stopPropagation(); moveSection(path, 1); } }),
				el('button', { type: 'button', className: 'button button-small', text: 'Duplicate', onClick: function (event) { event.stopPropagation(); duplicateSection(path); } }),
				el('button', { type: 'button', className: 'button button-small button-link-delete', text: 'Remove', onClick: function (event) { event.stopPropagation(); removeSection(path); } })
			])
		]));

		var body = el('div', { className: 'igp-pro-section-body' });
		if (!block) {
			body.appendChild(el('p', { text: 'Unknown block. This section cannot be edited until the block is registered.' }));
		} else {
			if (!section.data || typeof section.data !== 'object') {
				section.data = {};
			}
			renderFields(body, (block.schema || {}).fields || {}, section.data, path, []);
		}

		if (section.block_id === 'section') {
			if (!Array.isArray(section.children)) {
				section.children = [];
			}
			var childSelect = el('select', {});
			state.blocks.forEach(function (candidate) { childSelect.appendChild(el('option', { value: candidate.id, text: candidate.title })); });
			body.appendChild(el('div', { className: 'igp-pro-child-section-control' }, [
				el('h4', { text: 'Child blocks' }),
				childSelect,
				el('button', { type: 'button', className: 'button', text: 'Add child block', onClick: function () { addSection(childSelect.value, path); } })
			]));
			var childList = el('div', { className: 'igp-pro-child-section-list' });
			section.children.forEach(function (child, childIndex) { childList.appendChild(renderSection(child, path.concat([childIndex]), depth + 1)); });
			body.appendChild(childList);
		}
		wrapper.appendChild(body);
		return wrapper;
	}

	function postMatchesSearch(post, term) {
		term = String(term || '').trim().toLowerCase();
		if (!term) {
			return true;
		}
		return [formatPostOption(post), post.status || '', post.post_type || '', String(post.id || '')].join(' ').toLowerCase().indexOf(term) !== -1;
	}

	function populatePostSelect(select, term) {
		var current = state.selectedPostId || select.value || '';
		select.innerHTML = '';
		select.appendChild(el('option', { value: '', text: 'Select a page, tour, or destination' }));
		state.posts.filter(function (post) { return postMatchesSearch(post, term); }).forEach(function (post) { select.appendChild(el('option', { value: post.id, text: formatPostOption(post) })); });
		var hasCurrent = Array.prototype.some.call(select.options, function (option) { return String(option.value) === String(current); });
		select.value = hasCurrent ? current : '';
	}

	function searchContentTargets(term) {
		term = String(term || '').trim();
		if (state.searchTimer) {
			window.clearTimeout(state.searchTimer);
		}
		state.searchTimer = window.setTimeout(function () {
			if (!term || term.length < 2) {
				return;
			}
			state.searchingPosts = true;
			searchPosts(term, 80).then(function () {
				state.searchingPosts = false;
				var select = document.getElementById('igp-pro-content-target-select');
				if (select) {
					populatePostSelect(select, state.postSearch);
				}
			}).catch(function () { state.searchingPosts = false; });
		}, 350);
	}

	function renderToolbar() {
		var select = el('select', { id: 'igp-pro-content-target-select', onChange: function (event) { state.selectedPostId = event.target.value; } });
		populatePostSelect(select, state.postSearch);
		var searchInput = el('input', { type: 'search', value: state.postSearch || '', placeholder: 'Search by title, type, status, or #ID', autocomplete: 'off', onInput: function (event) { state.postSearch = event.target.value; populatePostSelect(select, state.postSearch); searchContentTargets(state.postSearch); } });
		return el('div', { className: 'igp-pro-admin-card igp-pro-toolbar' }, [
			el('div', { className: 'igp-pro-content-target-control' }, [el('label', { text: 'Content target' }), searchInput, select, el('small', { text: 'Naming convention preserved: [post_type] Title #ID. Type two or more characters to search beyond the initial recent list.' })]),
			el('button', { type: 'button', className: 'button', text: 'Load', onClick: loadSelectedPost }),
			el('button', { type: 'button', className: 'button button-primary', text: 'Save graph', onClick: saveCurrentGraph }),
			el('button', { type: 'button', className: 'button', text: 'Refresh list', onClick: bootstrap })
		]);
	}

	function renderMetaCard() {
		return el('div', { className: 'igp-pro-admin-card igp-pro-meta' }, [
			el('label', { text: 'Meta description' }),
			el('textarea', { rows: 3, maxlength: 320, value: state.metaDescription || '', onInput: function (event) { state.metaDescription = event.target.value; markDirty(); } }),
			el('small', { text: 'Stored as structured meta for SEO engine use.' })
		]);
	}

	function renderSections() {
		ensureGraph();
		var card = el('div', { className: 'igp-pro-admin-card' });
		card.appendChild(el('h2', { text: 'Sections' }));
		if (!state.graph.sections.length) {
			card.appendChild(el('div', { className: 'igp-pro-empty', text: 'No Content Graph sections yet. Add a section from the side panel.' }));
			return card;
		}
		var list = el('div', { className: 'igp-pro-section-list' });
		state.graph.sections.forEach(function (section, index) { list.appendChild(renderSection(section, [index], 0)); });
		card.appendChild(list);
		return card;
	}

	function renderSidePanel() {
		var blockSelect = el('select', { id: 'igp-pro-add-block-select' });
		state.blocks.forEach(function (block) { blockSelect.appendChild(el('option', { value: block.id, text: block.title })); });
		var jsonPreview = el('textarea', { className: 'igp-pro-json-preview', readonly: true, value: JSON.stringify({ graph: state.graph, meta: { description: state.metaDescription || '' } }, null, 2) });
		return el('div', { className: 'igp-pro-side-panel' }, [
			el('div', { className: 'igp-pro-admin-card igp-pro-add-section' }, [el('h2', { text: 'Add section' }), blockSelect, el('button', { type: 'button', className: 'button button-primary', text: 'Add selected block', onClick: function () { addSection(blockSelect.value, []); } })]),
			el('div', { className: 'igp-pro-admin-card igp-pro-import-export' }, [
				el('h2', { text: 'Import / Export' }),
				el('div', { className: 'igp-pro-button-row' }, [el('button', { type: 'button', className: 'button', text: 'Export saved JSON', onClick: exportGraph }), el('label', { className: 'button', text: 'Import JSON', for: 'igp-pro-import-file' })]),
				el('input', { id: 'igp-pro-import-file', type: 'file', accept: 'application/json,.json', style: 'display:none', onChange: importFile }),
				el('small', { text: 'Import validates the payload but does not save until you click Save graph.' })
			]),
			el('div', { className: 'igp-pro-admin-card' }, [el('h2', { text: 'Current JSON preview' }), jsonPreview])
		]);
	}

	function renderLoadedPostLinks() {
		if (!state.loadedPost) {
			return null;
		}
		var sourceLabel = state.contentSource ? ' · Source: ' + state.contentSource : '';
		return el('p', { className: 'description' }, [
			document.createTextNode('Loaded: ' + state.loadedPost.title + ' #' + state.loadedPost.id + ' (' + state.loadedPost.post_type + '). '),
			state.loadedPost.edit_url ? el('a', { href: state.loadedPost.edit_url, target: '_blank', rel: 'noopener', text: 'Open WP editor' }) : null,
			document.createTextNode(' · '),
			state.loadedPost.view_url ? el('a', { href: state.loadedPost.view_url, target: '_blank', rel: 'noopener', text: 'View frontend' }) : null,
			document.createTextNode(sourceLabel)
		]);
	}

	function render() {
		root.innerHTML = '';
		if (state.loading) {
			root.appendChild(el('div', { className: 'igp-pro-admin-card', text: 'Loading editor…' }));
			return;
		}
		var app = el('div', { className: 'igp-pro-content-editor' });
		app.appendChild(renderToolbar());
		if (state.status) {
			app.appendChild(el('div', { className: 'igp-pro-status ' + (state.statusType ? 'is-' + state.statusType : ''), text: state.status }));
		}
		var links = renderLoadedPostLinks();
		if (links) {
			app.appendChild(links);
		}
		if (state.loadedPost) {
			var grid = el('div', { className: 'igp-pro-editor-grid' });
			grid.appendChild(el('div', {}, [renderMetaCard(), renderSections()]));
			grid.appendChild(renderSidePanel());
			app.appendChild(grid);
		} else {
			app.appendChild(el('div', { className: 'igp-pro-empty', text: 'Select a content target and click Load.' }));
		}
		root.appendChild(app);
	}

	function bootstrap() {
		state.loading = true;
		render();
		request('igp_pro_content_editor_bootstrap', {}).then(function (data) {
			state.posts = data.posts || [];
			state.blocks = data.blocks || [];
			if (!state.selectedPostId && state.posts[0]) {
				state.selectedPostId = String(state.posts[0].id);
			}
			state.loading = false;
			state.status = 'Editor ready. Select a target and load its Content Graph.';
			state.statusType = '';
			render();
		}).catch(function (error) {
			state.loading = false;
			state.status = error.message;
			state.statusType = 'error';
			render();
		});
	}

	function loadSelectedPost() {
		if (!state.selectedPostId) {
			setStatus('Select a page, tour, or destination first.', 'error');
			return;
		}
		request('igp_pro_load_content_graph', { post_id: state.selectedPostId }).then(function (data) {
			state.loadedPost = data.post || null;
			state.graph = data.graph || { version: 'v1', sections: [] };
			state.metaDescription = data.meta_description || '';
			state.contentSource = data.source || '';
			state.dirty = false;
			state.collapsed = {};
			state.status = data.message || 'Content Graph loaded.';
			state.statusType = 'success';
			render();
		}).catch(function (error) { setStatus(error.message || t('loadError', 'Could not load content graph.'), 'error'); });
	}

	function saveCurrentGraph() {
		if (!state.loadedPost) {
			setStatus('Load a content target before saving.', 'error');
			return;
		}
		ensureGraph();
		request('igp_pro_save_content_graph', { post_id: state.loadedPost.id, graph: JSON.stringify(state.graph), meta_description: state.metaDescription || '' }).then(function (data) {
			state.graph = data.graph || state.graph;
			state.metaDescription = data.meta_description || '';
			state.contentSource = data.source || 'post_meta';
			state.dirty = false;
			state.status = data.message || 'Content Graph saved and synced to the WordPress editor.';
			state.statusType = 'success';
			render();
		}).catch(function (error) { setStatus(error.message || t('saveError', 'Could not save content graph.'), 'error'); });
	}

	function importFile(event) {
		var file = event.target.files && event.target.files[0];
		if (!file) {
			return;
		}
		var reader = new FileReader();
		reader.onload = function () {
			request('igp_pro_import_content_graph', { payload: String(reader.result || '') }).then(function (data) {
				state.graph = data.graph || { version: 'v1', sections: [] };
				if (data.meta_description) {
					state.metaDescription = data.meta_description;
				}
				state.dirty = true;
				state.status = data.message || 'Import validated. Click Save graph to persist it.';
				state.statusType = 'success';
				render();
			}).catch(function (error) { setStatus(error.message || t('importError', 'Import failed.'), 'error'); });
		};
		reader.readAsText(file);
		event.target.value = '';
	}

	function exportGraph() {
		if (!state.loadedPost) {
			setStatus('Load a content target before exporting.', 'error');
			return;
		}
		request('igp_pro_export_content_graph', { post_id: state.loadedPost.id }).then(function (data) {
			var json = JSON.stringify(data, null, 2);
			var blob = new Blob([json], { type: 'application/json' });
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = 'igp-content-graph-' + state.loadedPost.id + '.json';
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
			setStatus('Export generated from saved Content Graph.', 'success');
		}).catch(function (error) { setStatus(error.message || 'Export failed.', 'error'); });
	}

	bootstrap();
}());
