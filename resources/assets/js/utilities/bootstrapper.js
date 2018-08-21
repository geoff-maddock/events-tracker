import jQuery from 'jquery';

const $ = jQuery;

const Bootstrapper = {
    initApp(componentModules, applicationModules) {
        $('body').on('init-modules', (event, target) => {
            this.initModules(componentModules, target);
            this.initModules(applicationModules, target);
        });

        this.initModules(componentModules);
        this.initOnceModules(componentModules);
        this.initModules(applicationModules);
        this.initOnceModules(applicationModules);
    },

    initModules(modules, target = 'body') {
        modules.forEach((module) => {
            if (typeof module.init === 'function') module.init(target);
        });
    },

    initOnceModules(modules) {
        modules.forEach((module) => {
            if (typeof module.initOnce === 'function') module.initOnce();
        });
    },

    getApplicationModules(applicationMapping) {
        if (Object.prototype.hasOwnProperty.call(window, 'cadence') && Array.isArray(window.cadence.applicationModules)) {
            return window.cadence.applicationModules.reduce((result, moduleName) => {
                if (Object.prototype.hasOwnProperty.call(applicationMapping, moduleName)) {
                    result.push(applicationMapping[moduleName]);
                }
                return result;
            }, []);
        }
        return [];
    },
};

export default Bootstrapper;