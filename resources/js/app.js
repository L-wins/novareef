import { createApp } from 'vue';
import '../css/app.css';

// Aquí iremos registrando los componentes Vue de NovaReef

// Sombra en el navbar al hacer scroll
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var nav = document.getElementById('navbar');
        if (!nav) return;
        window.addEventListener('scroll', function () {
            nav.classList.toggle('scrolled', window.scrollY > 10);
        }, { passive: true });
    });
}());
