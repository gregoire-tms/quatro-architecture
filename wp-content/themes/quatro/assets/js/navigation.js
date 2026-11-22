/**
 * Navigation : menu plein écran sur mobile et sous-menus ouvrables au clavier ou au toucher.
 */
(() => {
    const root = document.documentElement;
    const toggle = document.querySelector('.menu-toggle');
    const label = toggle ? toggle.querySelector('.menu-toggle__label') : null;
    const submenuItems = () => document.querySelectorAll('.site-navigation .menu-item-has-children');

    const setMenu = (open) => {
        if (!toggle) {
            return;
        }
        toggle.setAttribute('aria-expanded', String(open));
        if (label) {
            label.textContent = open ? 'Fermer' : 'Menu';
        }
        root.classList.toggle('menu-open', open);
    };

    const setSubmenu = (item, open) => {
        item.classList.toggle('is-open', open);
        const button = item.querySelector(':scope > .submenu-toggle');
        if (button) {
            button.setAttribute('aria-expanded', String(open));
        }
    };

    const closeSubmenus = (except) => {
        submenuItems().forEach((item) => {
            if (item !== except) {
                setSubmenu(item, false);
            }
        });
    };

    if (toggle) {
        toggle.addEventListener('click', () => {
            setMenu(toggle.getAttribute('aria-expanded') !== 'true');
        });
    }

    submenuItems().forEach((item) => {
        const button = item.querySelector(':scope > .submenu-toggle');
        if (button) {
            button.addEventListener('click', () => {
                const open = !item.classList.contains('is-open');
                closeSubmenus(item);
                setSubmenu(item, open);
            });
        }

        // Le sous-menu se referme quand le focus clavier le quitte.
        item.addEventListener('focusout', (event) => {
            if (!item.contains(event.relatedTarget)) {
                setSubmenu(item, false);
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.site-navigation .menu-item-has-children')) {
            closeSubmenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const openItem = document.querySelector('.site-navigation .menu-item-has-children.is-open');
        if (openItem) {
            closeSubmenus();
            const button = openItem.querySelector(':scope > .submenu-toggle');
            if (button) {
                button.focus();
            }
            return;
        }

        if (root.classList.contains('menu-open')) {
            setMenu(false);
            toggle.focus();
        }
    });

    window.matchMedia('(min-width: 961px)').addEventListener('change', (event) => {
        if (event.matches) {
            setMenu(false);
        }
    });
})();
