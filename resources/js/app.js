import AirDatepicker from 'air-datepicker';
import localeEs from 'air-datepicker/locale/es';
import { createIcons, icons } from 'lucide';
import Swal from 'sweetalert2';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

import 'air-datepicker/air-datepicker.css';
import 'sweetalert2/dist/sweetalert2.css';

window.AirDatepicker = AirDatepicker;
window.Swal = Swal;
window.Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: {
        popup: 'app-toast',
        title: 'app-toast-title',
        icon: 'app-toast-icon',
        timerProgressBar: 'app-toast-progress',
    },
    didOpen(toast) {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    },
});

function initializeIcons() {
    createIcons({
        icons,
        attrs: {
            'stroke-width': 1.5,
        },
    });
}

function initializeDatepickers() {
    document.querySelectorAll('[data-datepicker]').forEach((element) => {
        if (element._airDatepicker) {
            return;
        }

        element._airDatepicker = new AirDatepicker(element, {
            locale: localeEs,
            dateFormat: 'dd/MM/yyyy',
            autoClose: true,
        });
    });
}

initializeDatepickers();
initializeIcons();
document.addEventListener('livewire:navigated', initializeDatepickers);
document.addEventListener('livewire:navigated', initializeIcons);
Livewire.hook('morph.updated', initializeIcons);

window.Alpine = Alpine;
window.Livewire = Livewire;

Alpine.store('theme', {
    theme: 'light',

    init() {
        if (document.documentElement.dataset.themeContext === 'light') {
            this.theme = 'light';
            this.updateTheme();

            return;
        }

        const savedTheme = localStorage.getItem('theme');
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

        this.theme = savedTheme || systemTheme;
        this.updateTheme();
    },

    toggle() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.theme);
        this.updateTheme();
    },

    updateTheme() {
        document.documentElement.classList.toggle('dark', this.theme === 'dark');
    },
});

Alpine.store('sidebar', {
    isExpanded: window.innerWidth >= 1280,
    isMobileOpen: false,
    isHovered: false,

    toggleExpanded() {
        this.isExpanded = !this.isExpanded;
        this.isMobileOpen = false;
    },

    toggleMobileOpen() {
        this.isMobileOpen = !this.isMobileOpen;
    },

    closeMobile() {
        this.isMobileOpen = false;
    },

    setHovered(value) {
        if (window.innerWidth >= 1280 && !this.isExpanded) {
            this.isHovered = value;
        }
    },
});

Livewire.start();
