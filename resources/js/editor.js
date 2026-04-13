// resources/js/editor.js
import tinymce from 'tinymce';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/models/dom';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/table';
import 'tinymce/plugins/image';
import 'tinymce/plugins/media';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/wordcount';

/**
 * Inline content stylesheet applied inside the TinyMCE iframe.
 *
 * @description Mirrors the site's dark/bronze theme (Cormorant Garamond body,
 *   Cinzel headings, panel background) so the editor surface matches the
 *   surrounding form inputs defined in resources/css/app.css.
 */
const CONTENT_STYLE = `
    @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap');

    body {
        background-color: #242434;
        color: #efe6d6;
        font-family: 'Cormorant Garamond', ui-serif, Georgia, 'Times New Roman', serif;
        font-size: 1rem;
        line-height: 1.7;
        padding: 0.75rem;
    }

    h1, h2, h3, h4 {
        font-family: 'Cinzel', ui-serif, Georgia, serif;
        letter-spacing: 0.08em;
        color: #d4b05c;
    }

    a { color: #d4b05c; }

    blockquote {
        border-left: 2px solid rgba(212, 176, 92, 0.5);
        padding-left: 1rem;
        color: #d5cab6;
        font-style: italic;
    }

    ul, ol { padding-left: 1.4rem; }

    hr {
        border: 0;
        border-top: 1px solid rgba(212, 176, 92, 0.28);
    }
`;

export function initEditor() {
    tinymce.init({
        selector: 'textarea.tinymce',
        base_url: '/build/vendor/tinymce',
        suffix: '.min',
        skin: 'oxide-dark',
        content_css: 'dark',
        content_style: CONTENT_STYLE,
        plugins: 'lists link table image media autolink',
        toolbar: 'bold italic underline | table bullist link  image media',
        menubar: false,
        statusbar: false,
        branding: false,
        height: 400,
        license_key: 'gpl',
    });
}
