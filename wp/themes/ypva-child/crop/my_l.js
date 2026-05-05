/* CropGuide Loader b1714384825164, only for use with an active CropGuide license, https://crop.guide/ (c) 2021 - 2024 PQINA Inc. */
(() => {
    var J = (e, r) => e.reduce((s, c) => s.then(u => r(c).then(f => u.concat([f]))), Promise.resolve([]));
    var j = e => {
        let r = new DataTransfer;
        return e.forEach(s => r.items.add(s)), Object.defineProperty(r, "items", {
            value: e.map(s => (s.getAsFile = () => s, s))
        }), r
    };
    var A = (e, r) => document.documentElement.addEventListener(e, r, !0);
    var K = (e, r) => {
        let s = j(r);
        e.files = s.files
    };
    var S = (e, r, s = {}, c = {}) => {
        let {
            bubbles: u = !0,
            detail: f,
            cancelable: a = !0
        } = c, l = new CustomEvent(r, {
            bubbles: u,
            cancelable: a,
            detail: f
        });
        return Object.assign(l, s), e.dispatchEvent(l), l
    };
    var be = ["x", "y", "screenX", "screenY", "layerX", "layerY", "pageX", "pageY", "offsetX", "offsetY", "clientX", "clientY"],
        Q = (e, {
            didIntercept: r,
            shouldHandleChange: s,
            shouldHandleDrop: c
        }, u) => {
            let f = (a, l, g) => J(Array.from(a), o => e(o, l, g));
            A("change", a => {
                let {
                    target: l
                } = a;
                if (l.ignoreChangeEvent || a.$cropguide) return;
                let g = s(a);
                !g || (r(a), a.stopImmediatePropagation(), f(l.files, g, l).then(o => {
                    u("handled selected files", o), l.ignoreChangeEvent = !0, K(l, o.filter(Boolean)), delete l.ignoreChangeEvent;
                    let m = S(l, "change", {
                        $cropguide: !0
                    });
                    u("dispatched CustomEvent", [l, m])
                }))
            }), A("drop", a => {
                if (a.$cropguide) return;
                let l = c(a);
                if (!l) return;
                r(a);
                let {
                    target: g
                } = a;
                a.preventDefault(), a.stopImmediatePropagation();
                let o = be.reduce((m, h) => (m[h] = a[h], m), {});
                f(a.dataTransfer.files, l, g).then(m => {
                    u("handled dropped files", m);
                    let h = j(m.filter(Boolean)),
                        T = S(g, "drop", {
                            ...o,
                            dataTransfer: {
                                effectAllowed: "all",
                                types: ["Files"],
                                files: h.files,
                                items: h.items,
                                getData: () => ""
                            },
                            $cropguide: !0
                        });
                    u("dispatched CustomEvent", [g, T])
                })
            })
        };
    var Z = () => window && "DataTransfer" in window;
    var N = (e, {
        parent: r,
        onerror: s
    }) => {
        let c = document.createElement("script");
        return c.defer = !0, c.async = !0, c.src = e, c.onerror = s, (r || document.head).append(c), c
    };
    var k = e => e && /input/i.test(e.tagName) && e.type === "file";
    var Fe = /png|jpeg|gif|bmp|webp|heic/,
        G = e => !e || !e.type ? !1 : Fe.test(e.type);
    var ee = e => !(!k(e.target) || !e.target.files.length || !Array.from(e.target.files).some(G));
    var te = e => !(k(e.target) || !e.dataTransfer || !e.dataTransfer.files.length || !Array.from(e.dataTransfer.files).some(G));
    var ve = e => e.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."),
        oe = e => {
            let r = Date.now(),
                s = "CropGuide";
            return e ? (...c) => {
                let u = Date.now() - r,
                    f = `${ve(u)}ms`.padStart(8, " "),
                    a = "";
                typeof c[0] == "string" ? (a = f + " " + s + " " + c[0], params = c.splice(1)) : (a = f, params = [...c]), params.length ? console.log(a, params) : console.log(a)
            } : () => {}
        };
    var re = "application/json",
        B = (e, r = {}, {
            method: s = "POST"
        } = {}) => fetch(e, {
            headers: {
                Accept: re,
                "Content-Type": re
            },
            method: s,
            body: JSON.stringify(r)
        }).catch(console.log);
    var ne = (e, r) => {
        if (r === "html" || e.matches(r)) return !0;
        let c = document.querySelector(r);
        return c ? c.contains(e) : !1
    };
    var ie = e => new File([e], e.name, {
        type: e.type,
        lastModified: e.lastModified
    });
    var se = () => {
        try {
            let [e, r, s] = getComputedStyle(document.documentElement).backgroundColor.match(/[0-9]+/g).map(u => parseInt(u, 10));
            return .2126 * e + .7152 * r + .0722 * s > 100
        } catch (e) {
            return !1
        }
    };
    var ce = () => {
        try {
            return localStorage.getItem("cgdebug") === "1"
        } catch (e) {}
    };
    var ae = (e, r) => !r || !r.length ? !0 : e && e.type && r.includes(e.type);
    var M = e => new File([e], e.name, {
        type: e.type,
        lastModified: e.lastModified
    });
    var Ce = /png|jpeg|gif|bmp|webp/,
        le = e => !e || !e.type ? !1 : Ce.test(e.type);
    ((e, r, {
        hookId: s,
        apiURL: c,
        localeURL: u,
        clientURL: f
    }) => {
        let l = new URL(r.currentScript.src).searchParams,
            g = l.get("debug") !== null || ce(l),
            o = oe(g);
        if (!Z()) return o("not supported on this browser");
        let m = l.get("c");
        if (!m) return o("clientId not found");
        let h = e[s],
            T = [];
        if (h) {
            if (h.fields) T = h.fields.map(t => typeof t == "string" ? {
                selector: t
            } : t), e[s] = void 0, o("local fields config loaded", T);
            else if (typeof h == "object") return o("client already initialised")
        }
        let F = (t, n, p = r.documentElement) => S(p, `${s}:${t}`, void 0, {
            detail: n
        });
        F("init"), o("init", m);
        let y, O, _, d, w, I = [],
            de = (t = "auto") => {
                if (t !== "auto") return t.toLowerCase() || "en";
                let n = (r.documentElement.lang || "en").substring(0, 2).toLowerCase();
                return /^(en|es|fr|de|ru|zh)$/i.test(n) ? n : "en"
            },
            X = (t, n, p) => {
                if (!p(n)) return o(`skip ${n.type} event`, n), !1;
                let v = t.find(({
                    selector: L
                }) => ne(n.target, L));
                return !v || v.field.disabled && !g ? (o(`skip ${n.type} event`, n), !1) : (o(`handle ${n.type} event`, n, v), v)
            },
            z = (t, n) => ({
                selector: n.selector,
                field: {
                    ...t.field,
                    ...n.field
                },
                editor: {
                    ...t.editor,
                    ...n.editor
                }
            }),
            pe = (t, n) => {
                t.field = {
                    ...t.field,
                    ...n.field
                }, t.editor = {
                    ...t.editor,
                    ...n.editor
                }
            },
            ue = (t, n) => {
                let {
                    heic: p = !1
                } = n || {};
                return !!(t && p && /heic/.test(t.type))
            },
            q = () => {
                let {
                    banner: t
                } = y;
                w = T.length ? T.map(i => {
                    let D = y.fields.find(b => b.selector === i.selector) || y.fields.find(b => b.selector === "html");
                    return z(D, i)
                }) : y.fields, o("fields config", w), I.length && (I.forEach(i => V(...i)), I.length = 0);
                let {
                    userAgent: n,
                    maxTouchPoints: p,
                    platform: v
                } = navigator, L = /^mac/i.test(v), E = /iPhone|iPad|iPod/.test(n) || L && p >= 1;
                if (E) {
                    let i = r.createElement("style");
                    i.textContent = ".CropGuideDocumentLock,.CropGuideDocumentLock body {height: var(--crop-guide-document-height);overflow-y: hidden;box-sizing: border-box;}.CropGuideDocumentLock body {position:relative;}.CropGuideDocumentLock .CropGuideFrame {height: 100% !important;}", r.head.append(i)
                }
                let x, H = () => r.documentElement.style.setProperty("--crop-guide-document-height", `${window.innerHeight}px`),
                    me = () => {
                        o("will show editor"), d.style.pointerEvents = "all", d.style.width = "100%", d.style.height = "100vh", !!E && H()
                    },
                    fe = i => {
                        if (o("did show editor"), F("open", null, i), !E) {
                            d.focus();
                            return
                        }
                        x === void 0 && (x = e.scrollY), r.documentElement.classList.add("CropGuideDocumentLock"), H(), e.addEventListener("resize", H)
                    },
                    ge = () => {
                        o("will hide editor"), d.style.pointerEvents = "none", !!E && (e.removeEventListener("resize", H), r.documentElement.classList.remove("CropGuideDocumentLock"), e.scrollTo(0, x), x = void 0)
                    },
                    he = i => {
                        o("did hide editor"), F("close", null, i), d.style.width = 0, d.style.height = 0, i && i.focus()
                    };
                Q((i, D, b) => new Promise($ => {
                    if ((!le(i) || !ae(i, D.field.accept)) && !ue(i, {
                            heic: D.field.convertHeic
                        })) return o("ignore", i), F("ignore", {
                        src: i
                    }, b), $(i);
                    o("edit", i);
                    let ye = se(),
                        Ee = {
                            locale: O,
                            banner: t,
                            pageIsBright: ye,
                            willShowEditor: me,
                            didShowEditor: () => fe(b),
                            willHideEditor: ge,
                            didHideEditor: () => he(b)
                        },
                        R = () => {
                            d.removeEventListener("load", R), d.style.pointerEvents = "all", _(i, {
                                log: o,
                                env: Ee,
                                requirements: D
                            }).then(C => {
                                if (!C) {
                                    $(M(i));
                                    return
                                }
                                let P = ie(C);
                                o("processed", P), B(`${c}/meter`, {
                                    clientId: m
                                }), F("process", {
                                    src: i,
                                    dest: P
                                }, b), $(P)
                            }).catch(C => {
                                let P = C;
                                C.hasOwnProperty("message") ? P = {
                                    error: C.message + " " + C.stack
                                } : typeof C == "string" && (P = {
                                    error: C
                                });
                                let {
                                    error: Y
                                } = P;
                                if (/image too small/i.test(Y)) return o("image too small", i), F("invalid", {
                                    src: i
                                }, b), $();
                                o("error", Y), B(`${c}/report`, {
                                    clientId: m,
                                    error: Y
                                }), F("error", {
                                    src: i,
                                    error: Y
                                }, b), $(M(i))
                            }).finally(() => {
                                d.style.pointerEvents = "none"
                            })
                        };
                    d.isConnected ? R() : (d.addEventListener("load", R), document.body.append(d))
                }), {
                    didIntercept: i => F("intercept", i),
                    shouldHandleChange: i => X(w, i, ee),
                    shouldHandleDrop: i => X(w, i, te)
                }, o), F("load")
            },
            U = (t, n, p) => {
                if (!t) {
                    o(`wait for ${n} to load`);
                    return
                }
                p()
            },
            V = (t, n) => {
                if (o("update config", t, n), !w) return I.push([t, n]);
                if (typeof t != "string" && (n = t, t = void 0), t && !w.find(p => p.selector === t)) {
                    let p = z(y.fields.find(v => v.selector === "html"), {
                        ...n,
                        selector: t
                    });
                    return w.unshift(p), o("add field", p)
                }
                w.filter(p => !t || p.selector === t).forEach(p => {
                    pe(p, n), o("update field", p)
                })
            };
        e[s] = {
            updateConfig: V,
            _setLocale: t => {
                o("locale loaded", t), O = t, U(_, "editor", q)
            },
            _setEditor: t => {
                o("client loaded"), _ = t, U(O, "locale", q)
            }
        };
        let W = c + "/config/" + m;
        o("load config", W), fetch(W).then(t => t.text()).then(t => {
            o("config loaded");
            try {
                y = JSON.parse(t, (E, x) => x === null ? void 0 : x)
            } catch (E) {
                o("failed to parse config", t);
                return
            }
            if (o("config parsed", y), y.status === "Inactive") {
                o("inactive", y);
                return
            }
            let n = de(y.locale);
            o("inject locale", u, n), N(u + `/${n}.js`, {
                onerror: E => o("locale inject error", E || {})
            });
            let L = ".PinturaButtonExport{color:rgb(var(--color-background))!important;background:rgb(var(--color-foreground))!important}";
            d = r.createElement("iframe"), d.onerror = E => o("iframe load error", E), d.allowtransparency = !0, d.className = "CropGuideFrame", d.tabIndex = "-1", d.style.cssText = "position:fixed;left:0;top:0;width:0;height:0;border:0;z-index:2147483647;pointer-events:none;touch-action:manipulation;user-select:none;", o("inject client", f), d.srcdoc = '<!DOCTYPE html><html lang="' + n + '"><meta charset="utf-8"><title>crop.guide</title><style>' + L + '</style><script src="' + f + '"><\/script></html>', o("append client frame"), r.body.append(d)
        }).catch(t => o("config load error", t))
    })(window, document, {
        hookId: "$cropguide",
        apiURL: "https://app.crop.guide/api",
        localeURL: "https://yezraelperez.es/wp-content/themes/ypva-child/crop/",
        clientURL: "https://yezraelperez.es/wp-content/themes/ypva-child/crop/l.js"
    });
})();