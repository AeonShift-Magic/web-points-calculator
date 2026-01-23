import './bootstrap.js';
import './styles/web.css';
import './styles/datatables.min.css';

function mobileMenuTrigger() {
    const toggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('header-menu');

    if (!toggle || !menu) return;

    // remove any existing listener before adding a new one
    toggle.removeEventListener('click', toggle._handler || (() => {
    }));

    const handler = () => {
        menu.classList.toggle('menu-open');
    };

    toggle.addEventListener('click', handler);
    toggle._handler = handler; // store reference to remove later
}

(function () {
    mobileMenuTrigger();
})();

document.addEventListener('turbo:load', () => {
    if (typeof initCKEditor === 'function') {
        initCKEditor();
    }

    if (typeof initMap === 'function' && typeof google !== 'undefined') {
        const mapContainer = document.getElementById("gmap");
        if (mapContainer) {
            initMap();
        }
    }

    mobileMenuTrigger();
});

function initCKEditor() {
    const editorEls = document.querySelectorAll('.js-ckeditor');
    if (editorEls.length > 0) {
        editorEls.forEach(el => {
            if (el.ckeditorInstance) el.ckeditorInstance.destroy();
            ClassicEditor.create(el).then(editor => {
                el.ckeditorInstance = editor;
            });
        });
    }
}
