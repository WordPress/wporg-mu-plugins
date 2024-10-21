(()=>{"use strict";const e=window.wp.i18n;window.addEventListener("load",(()=>{const t=document.querySelector(".wp-block-wporg-chapter-list"),r=t?.querySelector(".wporg-chapter-list__toggle"),s=t?.querySelector(".wporg-chapter-list__list");r&&s&&r.addEventListener("click",(function(){"true"===r.getAttribute("aria-expanded")?(r.setAttribute("aria-expanded",!1),s.removeAttribute("style")):(r.setAttribute("aria-expanded",!0),s.setAttribute("style","display:block;"))})),t&&(t.classList.toggle("has-js-control"),t.querySelectorAll(".page_item_has_children").forEach((t=>{const r=t.querySelector(":scope > a");r.remove();const s=t.querySelector(":scope > ul"),a=document.createElement("button");a.setAttribute("aria-expanded",!1),
// translators: %s link title.
a.setAttribute("aria-label",(0,e.sprintf)((0,e.__)("Open %s submenu","wporg"),r.innerText)),a.onclick=()=>{s.classList.toggle("is-open");const t=a.getAttribute("aria-expanded");a.setAttribute("aria-expanded","false"===t),"false"===t?a.setAttribute("aria-label",
// translators: %s link title.
// translators: %s link title.
(0,e.sprintf)((0,e.__)("Close %s submenu","wporg"),r.innerText)):a.setAttribute("aria-label",
// translators: %s link title.
// translators: %s link title.
(0,e.sprintf)((0,e.__)("Open %s submenu","wporg"),r.innerText))};const n=document.createElement("span");n.className="wporg-chapter-list__button-group",n.append(a,r),t.insertBefore(n,s),(t.classList.contains("current_page_item")||t.classList.contains("current_page_ancestor"))&&(s.classList.toggle("is-open"),a.setAttribute("aria-expanded",!0),a.setAttribute("aria-label",
// translators: %s link title.
// translators: %s link title.
(0,e.sprintf)((0,e.__)("Close %s submenu","wporg"),r.innerText)))})))}))})();