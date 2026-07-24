/**
 * GC-Stats — Admin: rich text editor for news article content
 *
 * TinyMCE, self-hosted via npm (GPL, no Tiny Cloud account/API key). Only
 * the plugins/theme/skin actually used are imported as ES modules so
 * everything ships inside the Vite bundle instead of being fetched from a
 * runtime `base_url` — see https://www.tiny.cloud/docs/tinymce/latest/webpack-es6/
 * for the pattern this follows.
 *
 * Output is intentionally kept inside the allow-list enforced server-side
 * by App\Services\HtmlSanitizer (run on every save in NewsController) — the
 * `formats` override below forces plain <u>/<s> instead of TinyMCE's
 * default <span style="text-decoration:...">, and forecolor/backcolor's
 * <span style="color:/background-color:..."> output is exactly what that
 * sanitizer allow-lists for `style`.
 *
 * Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
 * Repository: https://github.com/GC-Stats/Website
 */

import tinymce from 'tinymce/tinymce';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';

import 'tinymce/skins/ui/oxide-dark/skin.css';
import '../../../css/admin/news/editor-chrome.css';
import uiContentCss from 'tinymce/skins/ui/oxide-dark/content.css?url';
import editorContentCss from '../../../css/admin/news/editor-content.css?url';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/table';
import 'tinymce/plugins/image';
import 'tinymce/plugins/code';

if (document.getElementById('news-content-editor')) {
    tinymce.init({
        selector: '#news-content-editor',
        license_key: 'gpl',
        promotion: false,
        branding: false,
        skin: false,
        content_css: [uiContentCss, editorContentCss],
        height: 480,
        plugins: 'lists link table image code',
        menubar: false,
        toolbar: 'blocks | bold italic underline strikethrough | forecolor backcolor | bullist numlist | blockquote link image table | code',
        block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6',
        formats: {
            underline: { inline: 'u', exact: true },
            strikethrough: { inline: 's', exact: true },
        },
        table_default_attributes: {},
        table_default_styles: {},
    });
}
