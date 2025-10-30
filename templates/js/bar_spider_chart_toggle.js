xsevChartToggle = function (parent_id) {
    // --- kleine Helfer ---
    const $id = (id) => document.getElementById(id);
    const qs = (root, sel) => root ? root.querySelector(sel) : null;
    const qsa = (root, sel) => root ? root.querySelectorAll(sel) : [];
    const hide = (el) => {
        if (el) el.style.display = 'none';
    };
    const show = (el) => {
        if (el) el.style.display = '';
    };
    const addClass = (el, cls) => {
        if (el) el.classList.add(cls);
    };
    const removeClass = (el, cls) => {
        if (el) el.classList.remove(cls);
    };

    this.parent = $id(parent_id);
    if (!this.parent) return;

    // Wenn nur 1 Button vorhanden ist, Button-Gruppe ausblenden und beenden
    if (qsa(this.parent, 'button').length === 1) {
        const btnGroup = qs(this.parent, '.btn-group');
        hide(btnGroup);
        return;
    }

    // Buttons
    this.bar_chart_button = qs(this.parent, '.bar_chart_button');
    this.spider_chart_button = qs(this.parent, '.spider_chart_button');
    this.left_right_chart_button = qs(this.parent, '.left_right_chart_button');

    // Charts
    this.bar_chart = qs(this.parent, '.bar_chart');
    this.spider_chart = qs(this.parent, '.spider_chart');
    this.left_right_chart = qs(this.parent, '.left_right_chart');

    const self = this;

    // Erste (vorhandene) Schaltfläche bestimmen
    this.first_button =
        this.bar_chart_button ||
        this.spider_chart_button ||
        this.left_right_chart_button ||
        null;

    // Warten, bis ggf. Canvas geladen ist, dann andere Charts ausblenden
    this.hideIfLoaded = function hideIfLoaded(depth) {
        depth = depth || 0;

        const spiderHasCanvas = self.spider_chart ? qsa(self.spider_chart, 'canvas').length > 0 : false;
        const lrHasCanvas = self.left_right_chart ? qsa(self.left_right_chart, 'canvas').length > 0 : false;

        if (spiderHasCanvas || lrHasCanvas || depth > 100) {
            // Spider nur verstecken, wenn sie nicht die erste aktive ist
            if (self.spider_chart_button && self.first_button && self.spider_chart_button !== self.first_button) {
                hide(self.spider_chart);
            }
            hide(self.left_right_chart);
        } else {
            setTimeout(function () {
                self.hideIfLoaded(depth + 1);
            }, 100);
        }
        return self;
    };

    if (this.first_button) addClass(this.first_button, 'active');
    this.hideIfLoaded(0);

    this.deactivateButtons = function () {
        removeClass(self.bar_chart_button, 'active');
        removeClass(self.spider_chart_button, 'active');
        removeClass(self.left_right_chart_button, 'active');

        hide(self.bar_chart);
        hide(self.spider_chart);
        hide(self.left_right_chart);
    };

    // Click-Handler (mit preventDefault wie "return false")
    if (this.bar_chart_button) {
        this.bar_chart_button.addEventListener('click', function (e) {
            e.preventDefault();
            self.deactivateButtons();
            addClass(self.bar_chart_button, 'active');
            show(self.bar_chart);
            // In der jQuery-Version wird printFeedback() aufgerufen.
            // Nur ausführen, wenn vorhanden:
            if (typeof self.printFeedback === 'function') self.printFeedback();
            return false;
        });
    }

    if (this.spider_chart_button) {
        this.spider_chart_button.addEventListener('click', function (e) {
            e.preventDefault();
            self.deactivateButtons();
            addClass(self.spider_chart_button, 'active');
            show(self.spider_chart);
            return false;
        });
    }

    if (this.left_right_chart_button) {
        this.left_right_chart_button.addEventListener('click', function (e) {
            e.preventDefault();
            self.deactivateButtons();
            addClass(self.left_right_chart_button, 'active');
            show(self.left_right_chart);
            return false;
        });
    }
};
