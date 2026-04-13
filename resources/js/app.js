import './bootstrap';

if (document.querySelector('textarea.tinymce')) {
    import('./editor').then(({ initEditor }) => initEditor());
}
