import '@adminlte/dist/js/adminlte.min.js';
import '@overlayscrollbars/browser/overlayscrollbars.browser.es5.min.js';
import '@popperjs/core/dist/umd/popper.min.js';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import Prism from 'prismjs';
import 'prismjs/components/prism-xml-doc';
import 'prismjs/themes/prism.css';

const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
const Default = {
    scrollbarTheme: 'os-theme-light',
    scrollbarAutoHide: 'leave',
    scrollbarClickScroll: true,
};
document.addEventListener('DOMContentLoaded', function() {
    // const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
    // if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
    //     OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
    //         scrollbars: {
    //             theme: Default.scrollbarTheme,
    //             autoHide: Default.scrollbarAutoHide,
    //             clickScroll: Default.scrollbarClickScroll,
    //         },
    //     });
    // }

    const repeaters = document.querySelectorAll(".repeater");

    repeaters.forEach(function(element)
    {
        const container = element.querySelector(".repeater-container");
        const itemAdd = element.querySelector(".repeater-add");
        const template = container.querySelector("template");

        let currentIndex = container.querySelectorAll(".repeater-item").length;

        itemAdd.addEventListener("click", function(e)
        {
            e.preventDefault();

            let html = template.innerHTML.replace(/__INDEX__/g, currentIndex);

            const tempTemplate = document.createElement("template");

            tempTemplate.innerHTML = html;

            const clone = tempTemplate.content.cloneNode(true);

            container.appendChild(clone);

            currentIndex++;
        });

        container.addEventListener("click", function(e)
        {
            const itemRemove = e.target.closest(".repeater-remove");

            if(itemRemove)
            {
                e.preventDefault();

                itemRemove.closest(".repeater-item").remove();
            }
        });
    });

});

const togglePassword = document.querySelectorAll(".togglePassword");

togglePassword.forEach(function(element)
{
    element.addEventListener('click', function(e)
    {
        e.preventDefault();

        let container = element.closest(".row");
        let input = container.querySelector("input");
        let icon = this.querySelector("i");

        if(input.type === "password")
        {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        }
        else
        {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    });
});

