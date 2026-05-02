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
		jsonOpen: false,
		contentSource: ''
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
				node.className = value;
			} else if (key === 'text') {
				node.textContent = value;
			} else if (key === 'html') {
				node.innerHTML = value;
			} else if (key.indexOf('on') === 0 && typeof value === 'function') {
				node.addEventListener(key.substring(2).toLowerCase(), value);
			} else if (key === 'value') {
				node.value = value === null || value === undefined ? '' : String(value);
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

	function updateSectionData(sectionIndex, path, value, options) {
		ensureGraph();
		var section = state.graph.sections[sectionIndex];
		if (!section) {
			return;
		}
		if (!section.data || typeof section.data !== 'object') {
			section.data = {};
		}
		var target = section.data;
		for (var i = 0; i < path.length - 1; i += 1) {
			var key = path[i];
			if (!target[key] || typeof target[key] !== 'object') {
				target[key] = typeof path[i + 1] === 'number' ? [] : {};
			}
			target = target[key];
		}
		target[path[path.length - 1]] = value;
		markDirty();

		// Do not re-render while the user types. Re-rendering replaced the input node
		// on every keypress, which made fields behave as if only one character could
		// be entered. Explicit structural actions can still request a render.
		if (options && options.render) {
			render();
		}
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

	function renderInput(label, field, value, onChange) {
		var type = field.type || 'string';
		var wrapper = el('div', { className: 'igp-pro-field' });
		var required = field.required ? el('span', { className: 'igp-pro-required', text: ' *' }) : null;
		var labelNode = el('label', {}, [document.createTextNode(field.label || labelize(label)), required]);
		wrapper.appendChild(labelNode);

		if (type === 'text') {
			wrapper.appendChild(el('textarea', {
				rows: 4,
				value: value || '',
				onInput: function (event) { onChange(event.target.value); }
			}));
			return wrapper;
		}

		if (type === 'number') {
			wrapper.appendChild(el('input', {
				type: 'number',
				min: field.min,
				max: field.max,
				value: value === undefined || value === null ? '' : value,
				onInput: function (event) {
					onChange(event.target.value === '' ? '' : Number(event.target.value));
				}
			}));
			return wrapper;
		}

		if (type === 'boolean') {
			var checkbox = el('input', {
				type: 'checkbox',
				onChange: function (event) { onChange(!!event.target.checked); }
			});
			checkbox.checked = !!value;
			wrapper.appendChild(el('label', { className: 'igp-pro-checkbox-label' }, [checkbox, document.createTextNode(' Enabled')]));
			return wrapper;
		}

		if (type === 'enum') {
			var select = el('select', {
				onChange: function (event) { onChange(event.target.value); }
			});
			(field.values || []).forEach(function (option) {
				select.appendChild(el('option', { value: option, text: labelize(option) }));
			});
			select.value = value || field.default || ((field.values || [])[0] || '');
			wrapper.appendChild(select);
			return wrapper;
		}

		if (type === 'relationship') {
			wrapper.appendChild(el('input', {
				type: 'text',
				value: Array.isArray(value) ? value.join(', ') : (value || ''),
				placeholder: '12, 24, 31',
				onInput: function (event) {
					var ids = String(event.target.value || '').split(/[^0-9]+/).map(function (part) {
						return parseInt(part, 10);
					}).filter(function (id, index, arr) {
						return id > 0 && arr.indexOf(id) === index;
					});
					onChange(ids);
				}
			}));
			wrapper.appendChild(el('small', { text: 'Enter post IDs separated by commas, spaces, or new lines.' }));
			return wrapper;
		}

		if (type === 'image') {
			var image = value && typeof value === 'object' && !Array.isArray(value) ? value : { url: value || '', alt: '' };
			var currentImage = Object.assign({ url: '', alt: '' }, image);
			var urlInput = el('input', {
				type: 'url',
				value: image.url || '',
				placeholder: 'https://example.com/image.jpg',
				onInput: function (event) {
					currentImage.url = event.target.value;
					onChange(Object.assign({}, currentImage));
				}
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
						currentImage = { url: attachment.url || '', alt: attachment.alt || attachment.title || '' };
						onChange(Object.assign({}, currentImage), { render: true });
					});
					frame.open();
				}
			});
			wrapper.appendChild(el('div', { className: 'igp-pro-image-control' }, [urlInput, mediaButton]));
			wrapper.appendChild(el('input', {
				type: 'text',
				value: image.alt || '',
				placeholder: 'Alt text',
				onInput: function (event) {
					currentImage.alt = event.target.value;
					onChange(Object.assign({}, currentImage));
				}
			}));
			if (image.url) {
				wrapper.appendChild(el('img', { className: 'igp-pro-image-preview', src: image.url, alt: image.alt || '' }));
			}
			return wrapper;
		}

		wrapper.appendChild(el('input', {
			type: (String(label).toLowerCase().indexOf('url') !== -1 ? 'url' : 'text'),
			value: value === undefined || value === null ? '' : value,
			onInput: function (event) { onChange(event.target.value); }
		}));
		return wrapper;
	}

	function renderFields(container, fields, data, sectionIndex, basePath) {
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
				renderFields(fieldset, field.fields || {}, data, sectionIndex, path);
				container.appendChild(fieldset);
				return;
			}

			if (type === 'repeater' || type === 'array') {
				container.appendChild(renderRepeater(fieldName, field, Array.isArray(value) ? value : [], sectionIndex, path, data));
				return;
			}

			container.appendChild(renderInput(fieldName, field, value, function (next, options) {
				updateSectionData(sectionIndex, path, next, options || {});
			}));
		});
	}

	function renderRepeater(fieldName, field, items, sectionIndex, path, data) {
		var wrapper = el('fieldset', { className: 'igp-pro-fieldset igp-pro-repeater' }, [el('legend', { text: field.label || labelize(fieldName) })]);
		var itemFields = field.fields || null;

		if (!itemFields || !Object.keys(itemFields).length) {
			wrapper.appendChild(el('textarea', {
				rows: 8,
				value: JSON.stringify(items, null, 2),
				onInput: function (event) {
					try {
						var parsed = event.target.value.trim() === '' ? [] : JSON.parse(event.target.value);
						if (Array.isArray(parsed)) {
							updateSectionData(sectionIndex, path, parsed);
						}
					} catch (error) {}
				}
			}));
			wrapper.appendChild(el('small', { text: 'Array JSON. Must be valid JSON before save.' }));
			return wrapper;
		}

		items.forEach(function (item, itemIndex) {
			var itemBox = el('div', { className: 'igp-pro-repeater-item' });
			itemBox.appendChild(el('div', { className: 'igp-pro-repeater-header' }, [
				el('strong', { text: labelize(fieldName) + ' #' + (itemIndex + 1) }),
				el('button', {
					type: 'button',
					className: 'button button-small',
					text: 'Remove',
					onClick: function () {
						items.splice(itemIndex, 1);
						updateSectionData(sectionIndex, path, items, { render: true });
					}
				})
			]));
			renderFields(itemBox, itemFields, data, sectionIndex, path.concat([itemIndex]));
			wrapper.appendChild(itemBox);
		});

		wrapper.appendChild(el('button', {
			type: 'button',
			className: 'button',
			text: 'Add item',
			onClick: function () {
				var nextItem = {};
				Object.keys(itemFields).forEach(function (childName) {
					var child = itemFields[childName] || {};
					nextItem[childName] = child.default !== undefined ? clone(child.default) : '';
				});
				items.push(nextItem);
				updateSectionData(sectionIndex, path, items, { render: true });
			}
		}));

		return wrapper;
	}

	function renderToolbar() {
		var select = el('select', {
			onChange: function (event) {
				state.selectedPostId = event.target.value;
				render();
			}
		});
		select.appendChild(el('option', { value: '', text: 'Select a page, tour, or destination' }));
		state.posts.forEach(function (post) {
			select.appendChild(el('option', { value: post.id, text: '[' + post.post_type + '] ' + post.title + ' #' + post.id }));
		});
		select.value = state.selectedPostId || '';

		return el('div', { className: 'igp-pro-admin-card igp-pro-toolbar' }, [
			el('div', {}, [el('label', { text: 'Content target' }), select]),
			el('button', { type: 'button', className: 'button', text: 'Load', onClick: loadSelectedPost }),
			el('button', { type: 'button', className: 'button button-primary', text: 'Save graph', onClick: saveCurrentGraph }),
			el('button', { type: 'button', className: 'button', text: 'Refresh list', onClick: bootstrap })
		]);
	}

	function renderMetaCard() {
		return el('div', { className: 'igp-pro-admin-card igp-pro-meta' }, [
			el('label', { text: 'Meta description' }),
			el('textarea', {
				rows: 3,
				maxlength: 320,
				value: state.metaDescription || '',
				onInput: function (event) {
					state.metaDescription = event.target.value;
					markDirty();
				}
			}),
			el('small', { text: 'Stored as structured meta for later SEO engine use. This phase does not output SEO tags.' })
		]);
	}

	function renderSection(section, index) {
		var block = getBlock(section.block_id);
		var title = block ? block.title : section.block_id;
		var sectionId = section.id || ('section_' + index);
		var isCollapsed = !!state.collapsed[sectionId];
		var wrapper = el('div', { className: 'igp-pro-section' + (isCollapsed ? ' is-collapsed' : '') });

		wrapper.appendChild(el('div', {
			className: 'igp-pro-section-header',
			onClick: function () {
				state.collapsed[sectionId] = !state.collapsed[sectionId];
				render();
			}
		}, [
			el('div', { className: 'igp-pro-section-title', text: (index + 1) + '. ' + title }),
			el('div', { className: 'igp-pro-section-actions' }, [
				el('button', { type: 'button', className: 'button button-small', text: 'Up', onClick: function (event) { event.stopPropagation(); moveSection(index, -1); } }),
				el('button', { type: 'button', className: 'button button-small', text: 'Down', onClick: function (event) { event.stopPropagation(); moveSection(index, 1); } }),
				el('button', { type: 'button', className: 'button button-small', text: 'Duplicate', onClick: function (event) { event.stopPropagation(); duplicateSection(index); } }),
				el('button', { type: 'button', className: 'button button-small button-link-delete', text: 'Remove', onClick: function (event) { event.stopPropagation(); removeSection(index); } })
			])
		]));

		var body = el('div', { className: 'igp-pro-section-body' });
		if (!block) {
			body.appendChild(el('p', { text: 'Unknown block. This section cannot be edited until the block is registered.' }));
		} else {
			if (!section.data || typeof section.data !== 'object') {
				section.data = {};
			}
			renderFields(body, (block.schema || {}).fields || {}, section.data, index, []);
		}
		wrapper.appendChild(body);
		return wrapper;
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
		state.graph.sections.forEach(function (section, index) {
			list.appendChild(renderSection(section, index));
		});
		card.appendChild(list);
		return card;
	}

	function renderSidePanel() {
		var blockSelect = el('select', { id: 'igp-pro-add-block-select' });
		state.blocks.forEach(function (block) {
			blockSelect.appendChild(el('option', { value: block.id, text: block.title }));
		});

		var jsonPreview = el('textarea', {
			className: 'igp-pro-json-preview',
			readonly: true,
			value: JSON.stringify({ graph: state.graph, meta: { description: state.metaDescription || '' } }, null, 2)
		});

		return el('div', { className: 'igp-pro-side-panel' }, [
			el('div', { className: 'igp-pro-admin-card igp-pro-add-section' }, [
				el('h2', { text: 'Add section' }),
				blockSelect,
				el('button', { type: 'button', className: 'button button-primary', text: 'Add selected block', onClick: function () { addSection(blockSelect.value); } })
			]),
			el('div', { className: 'igp-pro-admin-card igp-pro-import-export' }, [
				el('h2', { text: 'Import / Export' }),
				el('div', { className: 'igp-pro-button-row' }, [
					el('button', { type: 'button', className: 'button', text: 'Export saved JSON', onClick: exportGraph }),
					el('label', { className: 'button', text: 'Import JSON', for: 'igp-pro-import-file' })
				]),
				el('input', { id: 'igp-pro-import-file', type: 'file', accept: 'application/json,.json', style: 'display:none', onChange: importFile }),
				el('small', { text: 'Import validates the payload but does not save until you click Save graph.' })
			]),
			el('div', { className: 'igp-pro-admin-card' }, [
				el('h2', { text: 'Current JSON preview' }),
				jsonPreview
			])
		]);
	}

	function renderLoadedPostLinks() {
		if (!state.loadedPost) {
			return null;
		}
		var sourceLabel = '';
		if (state.contentSource === 'post_content') {
			sourceLabel = ' · Source: existing WordPress/Gutenberg IGP blocks';
		} else if (state.contentSource === 'post_meta') {
			sourceLabel = ' · Source: saved Content Graph meta';
		} else if (state.contentSource === 'empty') {
			sourceLabel = ' · Source: empty graph';
		}

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
			var main = el('div', {}, [renderMetaCard(), renderSections()]);
			grid.appendChild(main);
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
		}).catch(function (error) {
			setStatus(error.message || t('loadError', 'Could not load content graph.'), 'error');
		});
	}

	function saveCurrentGraph() {
		if (!state.loadedPost) {
			setStatus('Load a content target before saving.', 'error');
			return;
		}
		ensureGraph();
		request('igp_pro_save_content_graph', {
			post_id: state.loadedPost.id,
			graph: JSON.stringify(state.graph),
			meta_description: state.metaDescription || ''
		}).then(function (data) {
			state.graph = data.graph || state.graph;
			state.metaDescription = data.meta_description || '';
			state.contentSource = data.source || 'post_content';
			state.dirty = false;
			state.status = data.message || 'Content Graph saved and synced to the WordPress editor.';
			state.statusType = 'success';
			render();
		}).catch(function (error) {
			setStatus(error.message || t('saveError', 'Could not save content graph.'), 'error');
		});
	}

	function addSection(blockId) {
		var block = getBlock(blockId);
		if (!block) {
			return;
		}
		ensureGraph();
		var section = {
			id: 'section_' + Date.now() + '_' + Math.floor(Math.random() * 10000),
			block_id: block.id,
			data: createDefaultData(block)
		};
		state.graph.sections.push(section);
		markDirty();
		render();
	}

	function removeSection(index) {
		if (!window.confirm(t('confirmDelete', 'Remove this section?'))) {
			return;
		}
		state.graph.sections.splice(index, 1);
		markDirty();
		render();
	}

	function duplicateSection(index) {
		var copy = clone(state.graph.sections[index]);
		copy.id = 'section_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
		state.graph.sections.splice(index + 1, 0, copy);
		markDirty();
		render();
	}

	function moveSection(index, direction) {
		var next = index + direction;
		if (next < 0 || next >= state.graph.sections.length) {
			return;
		}
		var item = state.graph.sections.splice(index, 1)[0];
		state.graph.sections.splice(next, 0, item);
		markDirty();
		render();
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
			}).catch(function (error) {
				setStatus(error.message || t('importError', 'Import failed.'), 'error');
			});
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
		}).catch(function (error) {
			setStatus(error.message || 'Export failed.', 'error');
		});
	}

	bootstrap();
}());
