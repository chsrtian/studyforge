import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

const THEME_KEY = 'studyforge-theme';

window.setStudyForgeTheme = (mode) => {
	const useDark = mode === 'dark';
	document.documentElement.classList.toggle('dark', useDark);
	localStorage.setItem(THEME_KEY, useDark ? 'dark' : 'light');
};

document.addEventListener('click', (event) => {
	const navTarget = event.target.closest('[data-nav-loading]');
	if (navTarget) {
		document.body.classList.add('sf-page-transitioning');
	}
});

window.addEventListener('pageshow', () => {
	document.body.classList.remove('sf-page-transitioning');
});

Alpine.start();
