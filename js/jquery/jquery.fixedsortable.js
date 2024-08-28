//this code is created to fix this problem: http://stackoverflow.com/questions/4299241/
(function($, undefined) {

    $.widget("ui.fixedsortable", $.ui.sortable, {

        options: $.extend({}, $.ui.sortable.prototype.options, {
            fixed: []
        }),

        _create: function() {
            var o = this.options;
            this.containerCache = {};
            this.element.addClass("ui-sortable");

            //Get the items
            $.ui.sortable.prototype.refresh.apply(this, arguments);

            if (typeof this.options.fixed == "number") {
                var num = this.options.fixed
                this.options.fixed = [num];
            } else if (typeof this.options.fixed == "string" || typeof this.options.fixed == "object") {
                if (this.options.fixed.constructor != Array) {
                    var selec = this.options.fixed;
                    var temparr = [];
                    var temp = $(this.element[0])
                        .find(selec);
                    var x = this;


                    temp.each(function() {
                        var i;
                        for (i = 0; i < x.items.length && x.items[i].item.get(0) != this; ++i) {}
                        if (i < x.items.length) temparr.push(i);
                    });
                    this.options.fixed = temparr;
                }
            }


            //Let's determine if the items are being displayed horizontally
            this.floating = this.items.length ? o.axis === 'x' || (/left|right/)
                .test(this.items[0].item.css('float')) || (/inline|table-cell/)
                .test(this.items[0].item.css('display')) : false;

            //Let's determine the parent's offset
            this.offset = this.element.offset();

            //Initialize mouse events for interaction
            $.ui.sortable.prototype._mouseInit.apply(this, arguments);
        },

        _mouseCapture: function(event) {

            this._fixPrev = this._returnItems();
            return $.ui.sortable.prototype._mouseCapture.apply(this, arguments);
        },

        _mouseStart: function(event) {

            for (var i = 0; i < this.options.fixed.length; ++i) {
                var num = this.options.fixed[i];
                var elem = this.items[num];
                if (event.target == elem.item.get(0)) return false;
            }

            return $.ui.sortable.prototype._mouseStart.apply(this, arguments);
        },

        _rearrange: function(event, i, a, hardRefresh) {

            a ? a[0].appendChild(this.placeholder[0]) : i.item[0].parentNode.insertBefore(this.placeholder[0], (this.direction == 'down' ? i.item[0] : i.item[0].nextSibling));

            this._refix(i);



            //Various things done here to improve the performance:
            // 1. we create a setTimeout, that calls refreshPositions
            // 2. on the instance, we have a counter variable, that get's higher after every append
            // 3. on the local scope, we copy the counter variable, and check in the timeout, if it's still the same
            // 4. this lets only the last addition to the timeout stack through



            this.counter = this.counter ? ++this.counter : 1;
            var self = this,
                counter = this.counter;


            window.setTimeout(function() {
                if (counter == self.counter) self.refreshPositions(!hardRefresh); //Precompute after each DOM insertion, NOT on mousemove
            }, 0);

        },

        _refix: function(a) {
            var prev = this._fixPrev;
            var curr = this._returnItems();

            var Fixcodes = this.options.fixed;

            var NoFixed = [];
            var Fixed = [];
            var Mixed = []
            var post = [];


            for (var i = 0; i < Fixcodes.length; ++i) {
                var fix_index = Fixcodes[i];
                var fix_item = prev[fix_index];
                var j = 0;

                for (j = 0; j < curr.length && curr[j].item.get(0) != fix_item.item.get(0); ++j) {}

                curr.splice(j, 1);

                Fixed.push(fix_item);
            }

            for (var i = 0; i < curr.length; ++i) {
                if (curr[i].item.get(0) != this.currentItem.get(0)) {
                    NoFixed.push(curr[i]);
                }
            }

            var fix_count = 0;
            var nofix_count = 0;

            for (var i = 0; i < Fixed.length + NoFixed.length; ++i) {
                if (Fixcodes.indexOf(i) >= 0) {
                    Mixed.push(Fixed[fix_count++]);
                } else {
                    Mixed.push(NoFixed[nofix_count++]);
                }
            }

            var parent = this.currentItem.get(0)
                .parentNode;
            var allchild = parent.children;

            for (var i = 0; i < Mixed.length; ++i) {
                parent.removeChild(Mixed[i].item.get(0));
                parent.appendChild(Mixed[i].item.get(0));
            }
        },

        _returnItems: function(event) {

            this.containers = [this];
            var items = [];
            var self = this;
            var queries = [
                [$.isFunction(this.options.items) ? this.options.items.call(this.element[0], event, {
                    item: this.currentItem
                }) : $(this.options.items, this.element), this]
            ];
            var connectWith = $.ui.sortable.prototype._connectWith.apply;

            if (connectWith) {
                for (var i = connectWith.length - 1; i >= 0; i--) {
                    var cur = $(connectWith[i]);
                    for (var j = cur.length - 1; j >= 0; j--) {
                        var inst = $.data(cur[j], 'sortable');
                        if (inst && inst != this && !inst.options.disabled) {
                            queries.push([$.isFunction(inst.options.items) ? inst.options.items.call(inst.element[0], event, {
                                item: this.currentItem
                            }) : $(inst.options.items, inst.element), inst]);
                            this.containers.push(inst);
                        }
                    };
                };
            }

            for (var i = queries.length - 1; i >= 0; i--) {
                var targetData = queries[i][1];
                var _queries = queries[i][0];

                for (var j = 0, queriesLength = _queries.length; j < queriesLength; j++) {
                    var item = $(_queries[j]);

                    item.data('sortable-item', targetData); // Data for target checking (mouse manager)

                    items.push({
                        item: item,
                        instance: targetData,
                        width: 0,
                        height: 0,
                        left: 0,
                        top: 0
                    });
                };
            };

            return items;
        },


        value: function(input) {
            //console.log("test");
            $.ui.sortable.prototype.value.apply(this, arguments);
        }
    });

})(jQuery);