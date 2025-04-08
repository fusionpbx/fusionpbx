import '@adminlte/dist/js/adminlte.min.js';
import '@overlayscrollbars/browser/overlayscrollbars.browser.es5.min.js';
import '@popperjs/core/dist/umd/popper.min.js';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

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

        itemAdd.addEventListener("click", function(e)
        {
            e.preventDefault();

            const clone = template.content.cloneNode(true);

            container.appendChild(clone);
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

const togglePassword = document.getElementById('togglePassword');

if(togglePassword)
{
    document.getElementById('togglePassword').addEventListener('click', function()
    {
        let apikey = document.getElementById('api_key');
        let icon = this.querySelector('i');

        if(apikey.type === "password")
        {
            apikey.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        }
        else
        {
            apikey.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    });
}

