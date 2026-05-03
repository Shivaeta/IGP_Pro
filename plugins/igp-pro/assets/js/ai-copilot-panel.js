(function ($) {
  'use strict';

  function panel(id, content, isHtml) {
    var $el = $('#igp-ai-copilot-' + id);
    if (!$el.length) return;
    if (isHtml) $el.html(content || '<p>No data.</p>');
    else $el.html('<pre>' + escapeHtml(JSON.stringify(content || {}, null, 2)) + '</pre>');
  }

  function status(message, ok) {
    var $status = $('#igp-ai-copilot-status');
    $status.removeClass('hidden notice-error notice-success').addClass(ok ? 'notice-success' : 'notice-error').html('<p>' + escapeHtml(message) + '</p>');
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, function (ch) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'}[ch];
    });
  }

  function updateFromCompiled(compiled) {
    if (!compiled) return;
    panel('validation', compiled.validation || {});
    panel('mapping', compiled.mapping_report || []);
    panel('warnings', compiled.warnings || []);
    panel('media', compiled.media_requirements || []);
    panel('seo', compiled.seo || {});
    panel('graph', compiled.content_graph || {});
  }

  $(document).on('click', '[data-igp-ai-action]', function () {
    var action = $(this).data('igp-ai-action');
    var yaml = $('#igp-ai-copilot-yaml').val() || '';
    status((igpProAiCopilot.i18n && igpProAiCopilot.i18n.working) || 'Working…', true);

    $.post(igpProAiCopilot.ajaxUrl, {
      action: 'igp_pro_ai_copilot_' + action,
      nonce: igpProAiCopilot.nonce,
      yaml: yaml
    }).done(function (response) {
      if (!response || !response.success) {
        status((response && response.data && response.data.message) || 'Request failed.', false);
        return;
      }
      var data = response.data || {};
      status(action.replace('_', ' ') + ' complete.', true);
      if (action === 'parse') {
        panel('validation', {parsed: true});
        panel('graph', data.normalized || data);
      } else if (action === 'validate') {
        panel('validation', data.validation || {});
        panel('graph', data.normalized || {});
      } else if (action === 'compile') {
        updateFromCompiled(data);
      } else if (action === 'preview') {
        updateFromCompiled(data.compiled || {});
        panel('preview', data.preview || '', true);
      } else if (action === 'create_draft') {
        updateFromCompiled(data.compiled || {});
        panel('preview', '<p><strong>Draft created.</strong> Post ID: ' + escapeHtml(data.post_id || '') + '</p>' + (data.edit_link ? '<p><a href="' + escapeHtml(data.edit_link) + '">Open draft</a></p>' : ''), true);
      } else if (action === 'create_changeset') {
        panel('validation', data.validation_result || {});
        panel('mapping', data.mapping_report || []);
        panel('media', data.media_requirements || []);
        panel('preview', '<p><strong>Changeset created.</strong></p><p><code>' + escapeHtml(data.changeset_id || '') + '</code></p>', true);
      }
    }).fail(function (xhr) {
      var data = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : {};
      status(data.message || ((igpProAiCopilot.i18n && igpProAiCopilot.i18n.error) || 'Request failed.'), false);
      panel('warnings', data);
      if (data.data && data.data.validation) panel('validation', data.data.validation);
      if (data.data && data.data.mapping_report) panel('mapping', data.data.mapping_report);
    });
  });
})(jQuery);
