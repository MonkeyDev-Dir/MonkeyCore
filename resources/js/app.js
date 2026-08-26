import AirDatepicker from 'air-datepicker';
import localeEs from 'air-datepicker/locale/es';
import Swal from 'sweetalert2';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

import 'air-datepicker/air-datepicker.css';

window.AirDatepicker = AirDatepicker;
window.Swal = Swal;
window.Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

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
document.addEventListener('livewire:navigated', initializeDatepickers);

window.Alpine = Alpine;
window.Livewire = Livewire;

Alpine.store('theme', {
    theme: 'light',

    init() {
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
