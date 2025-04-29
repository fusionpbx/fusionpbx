import '@adminlte/dist/js/adminlte.min.js';
import '@overlayscrollbars/browser/overlayscrollbars.browser.es5.min.js';
import '@popperjs/core/dist/umd/popper.min.js';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import Prism from 'prismjs';
import 'prismjs/components/prism-xml-doc';
import 'prismjs/themes/prism.css';
import { TempusDominus } from '@eonasdan/tempus-dominus';
import '@eonasdan/tempus-dominus/dist/css/tempus-dominus.min.css'

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

    const datetimepickers = document.querySelectorAll(".datetimepicker");

    datetimepickers.forEach(function(element)
    {
        new TempusDominus(element, {
            display: {
                components: {
                    calendar: true,
                    date: true,
                    month: true,
                    year: true,
                    decades: true,
                    clock: true,
                    hours: true,
                    minutes: true,
                    seconds: false,
                    useTwentyfourHour: true
                }
            },
            localization: {
              format: 'yyyy-MM-dd HH:mm'
            }
        });

        element.addEventListener('change.td', function(e)
        {
            element.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });


    const buttonXMLCDR = document.querySelectorAll('.btn-xmlcdr');

    buttonXMLCDR.forEach(function(button)
    {
        const cell = button.closest("td");
        const icon = button.querySelector('i');
        const audio = cell.querySelector('audio');
        const progressBar = cell.querySelector('.progress-bar');

        button.addEventListener('click', function(e)
        {
            e.stopPropagation();

            if(audio.paused)
            {
                audio.play();
                icon.classList.remove('fa-play');
                icon.classList.add('fa-pause');
            }
            else
            {
                audio.pause();
                icon.classList.remove('fa-pause');
                icon.classList.add('fa-play');
            }
        });

        audio.addEventListener('timeupdate', function()
        {
            const progress = (audio.currentTime / audio.duration) * 100;
            progressBar.style.width = `${progress}%`;
        });

        audio.addEventListener('ended', function()
        {
            icon.classList.remove('fa-pause');
            icon.classList.add('fa-play');
            progressBar.style.width = `0%`;
        });
    });
});
