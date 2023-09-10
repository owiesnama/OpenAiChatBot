import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy/dist/vue.m";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});

function fillFormsWithStorageDate() {
    let ids = [
        ".quform-form-55",
        ".quform-form-56",
        ".quform-form-57",
        ".quform-form-66",
    ];
    let forms = document.querySelectorAll(ids.join(","));
    if (!forms.length) return;
    let program = JSON.parse(localStorage.getItem("program"));
    let withScolarship = JSON.parse(localStorage.getItem("withScholarship"));
    let [salseOwner, mainGroup] = getActorsByCountryId(program.country_id);
    let values = {
        ...getUtm(),
        schoolName: program.school_name,
        programTitle: program.title,
        withScholarship: !!withScolarship,
        title: program.meta.title,
        level: program.meta.level,
        speciality: program.meta.speciality,
        field: program.meta.field,
        salseOwner,
        mainGroup,
        affCode: localStorage.getItem("affiliate_code"),
    };
    console.log(values)
    forms.forEach((form) => {
        ids.forEach((id) => {
            let arrayNodes = Array.from(
                document.querySelectorAll(id + " input[type='hidden']")
            )
                .slice(Object.keys(values).length)
                .forEach((input, index) => {
                    input.setAttribute("value", Object.values(values)[index]);
                });
        });
    });
}
function getActorsByCountryId(id) {
    let actors;
    switch (id) {
        case 221:
            actors = ["17000087470", "Study In Turkey"];
            break;
        case 128:
            actors = ["17000089350", "Study In Malaysia"];
            break;
        default:
            actors = ["17000087470", "Study In Turkey"];
    }
    return actors;
}

function getUtm(){
    let utmParams = JSON.parse(localStorage.getItem("utm_params"))
    if(!utmParams) return

    return {
        source: utmParams.utm_source,
        medium: utmParams.utm_medium,
        campaing: utmParams.utm_campaign,
        content: utmParams.utm_content,
        term: utmParams.utm_term,
    }
}
fillFormsWithStorageDate();
