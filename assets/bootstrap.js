import { startStimulusApp } from '@symfony/stimulus-bundle'

if (!document.body.hasAttribute('data-no-stimulus')) {
    startStimulusApp()
}
