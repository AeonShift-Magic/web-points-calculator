// noinspection JSUnusedGlobalSymbols

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container', 'link'];

    toggle() {
        this.containerTarget.classList.toggle('hidden-form');
        this.linkTarget.classList.toggle('widget-toggle-open');
    }
}
