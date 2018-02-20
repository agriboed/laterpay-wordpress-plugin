!function(o){o(function(){function e(){var e={savePurchaseForm:o(".lp_js_savePurchaseForm"),cancelFormEdit:o(".lp_js_cancelEditingPurchaseForm"),restoreDefaults:o(".lp_js_restoreDefaultPurchaseForm"),buttonGroupButtons:".lp_js_buttonGroupButton",buttonGroupHint:".lp_js_buttonGroupHint",overlayOptions:".lp_js_overlayOptions",overlayShowFooter:".lp_js_overlayShowFooter",selected:"lp_is-selected",showHintOnTrue:"lp_js_showHintOnTrue",headerTitle:"lp_js_purchaseHeaderTitle",headerColor:"lp_js_purchaseHeaderColor",headerBgColor:"lp_js_purchaseHeaderBackgroundColor",purchaseBgColor:"lp_js_purchaseBackgroundColor",purchaseMainText:"lp_js_purchaseMainTextColor",purchaseDescription:"lp_js_purchaseDescriptionTextColor",buttonBgColor:"lp_js_purchaseButtonBackgroundColor",buttonTextColor:"lp_js_purchaseButtonTextColor",linkMainColor:"lp_js_purchaseLinkMainColor",linkHoverColor:"lp_js_purchaseLinkHoverColor",footerBgColor:"lp_js_purchaseFooterBackgroundColor",showFooter:"lp_js_overlayShowFooter",overlayHeader:".lp_purchase-overlay__header",overlayForm:".lp_purchase-overlay__form",overlayOptionTitle:".lp_purchase-overlay-option__title",overlayDescription:".lp_purchase-overlay-option__description",overlayLink:".lp_purchase-overlay__notification",overlayButton:".lp_purchase-overlay__submit",overlayFooter:".lp_purchase-overlay__footer",ratingsToggle:o("#lp_js_enableRatingsToggle"),ratingsForm:o("#lp_js_laterpayRatingsForm"),hideFreePostsToggle:o("#lp_js_hideFreePostsToggle"),hideFreePostsForm:o("#lp_js_laterpayHideFreePostsForm"),paidContentPreview:o("#lp_js_paidContentPreview"),previewSwitch:o("#lp_js_paidContentPreview").find(".lp_js_switchButtonGroup"),purchaseForm:o("#lp_js_purchaseForm"),purchaseButtonForm:o("#lp_js_purchaseButton"),purchaseButtonSwitch:o("#lp_js_purchaseButton").find(".lp_js_switchButtonGroup"),timePassesForm:o("#lp_js_timePasses"),timePassesSwitch:o("#lp_js_timePasses").find(".lp_js_switchButtonGroup")},r=function(){e.previewSwitch.click(function(){t(o(this))}),e.purchaseButtonSwitch.click(function(){a(o(this))}),e.timePassesSwitch.click(function(){l(o(this))}),o(e.overlayOptions).change(function(){n(o(this))}),o(e.overlayShowFooter).click(function(){s(o(this))}),e.savePurchaseForm.click(function(e){e.preventDefault();var r=o(this).parents("form");o("input[name=form]",r).val("overlay_settings"),i(r)}),e.cancelFormEdit.click(function(o){o.preventDefault(),u(lpVars.overlaySettings.current)}),e.restoreDefaults.click(function(o){o.preventDefault(),u(lpVars.overlaySettings["default"])}),e.ratingsToggle.change(function(){i(e.ratingsForm)}),e.hideFreePostsToggle.change(function(){i(e.hideFreePostsForm)})},t=function(r){var t=r.parents("form");switch(o(e.buttonGroupButtons,t).removeClass(e.selected),r.parent(e.buttonGroupButtons).addClass(e.selected),o("input[name=form]",t).val("paid_content_preview"),o("input:checked",t).val()){case"0":case"1":e.purchaseButtonForm.fadeIn(),e.timePassesForm.fadeIn(),e.purchaseForm.hide(),o(":input",e.purchaseForm).attr("disabled",!0);break;case"2":e.purchaseForm.fadeIn(),e.purchaseButtonForm.hide(),e.timePassesForm.hide(),o(":input",e.purchaseForm).attr("disabled",!1);break;default:e.purchaseForm.hide(),e.purchaseButtonForm.hide(),e.timePassesForm.hide()}i(t)},a=function(r){var t=r.parents("form");switch(o(e.buttonGroupButtons,t).removeClass(e.selected),r.parent(e.buttonGroupButtons).addClass(e.selected),o("input:checked",t).val()){case"0":t.find(e.buttonGroupHint).fadeOut();break;case"1":t.find(e.buttonGroupHint).fadeIn()}i(t)},l=function(r){var t=r.parents("form");switch(o(e.buttonGroupButtons,t).removeClass(e.selected),r.parent(e.buttonGroupButtons).addClass(e.selected),o("input:checked",t).val()){case"0":t.find(e.buttonGroupHint).fadeOut();break;case"1":t.find(e.buttonGroupHint).fadeIn()}i(t)},n=function(r){var t;r.hasClass(e.headerTitle)&&o(e.overlayHeader).text(o("."+e.headerTitle).val()),r.hasClass(e.headerColor)&&(t="color: "+o("."+e.headerColor).val()+" !important;",o(e.overlayHeader).css("background-color")&&(t+="; background-color: "+o("."+e.headerBgColor).val()+" !important;"),c(e.overlayHeader,t)),r.hasClass(e.headerBgColor)&&(t="background-color: "+o("."+e.headerBgColor).val()+" !important;",o(e.overlayHeader).css("color")&&(t+="; color: "+o("."+e.headerColor).val()+" !important;"),c(e.overlayHeader,t)),r.hasClass(e.purchaseBgColor)&&(t="background-color: "+o("."+e.purchaseBgColor).val()+" !important;",c(o(e.overlayForm),t)),r.hasClass(e.purchaseMainText)&&(t="color: "+o("."+e.purchaseMainText).val()+" !important;",c(o(e.overlayOptionTitle),t)),r.hasClass(e.purchaseDescription)&&(t="color: "+o("."+e.purchaseDescription).val()+" !important;",c(o(e.overlayDescription),t)),r.hasClass(e.buttonBgColor)&&(t="background-color: "+o("."+e.buttonBgColor).val()+" !important;",o(e.overlayButton).css("color")&&(t+="; color: "+o("."+e.buttonTextColor).val()+" !important;"),c(o(e.overlayButton),t)),r.hasClass(e.buttonTextColor)&&(t="color: "+o("."+e.buttonTextColor).val()+" !important;",o(e.overlayButton).css("background-color")&&(t+="; background-color: "+o("."+e.buttonBgColor).val()+" !important;"),c(o(e.overlayButton),t)),r.hasClass(e.linkMainColor)&&(t="color: "+o("."+e.linkMainColor).val()+" !important;",c(o(e.overlayLink+" a"),t),c(o(e.overlayLink),t)),r.hasClass(e.linkHoverColor)&&o(e.overlayLink+" a").hover(function(){t="color: "+o("."+e.linkHoverColor).val()+" !important;",c(o(e.overlayLink+" a"),t)},function(){t="color: "+o("."+e.linkMainColor).val()+" !important;",c(o(e.overlayLink+" a"),t)}),r.hasClass(e.footerBgColor)&&(t="background-color: "+o("."+e.footerBgColor).val()+" !important;",o(e.overlayFooter).is(":hidden")&&(t+="display: none;"),c(o(e.overlayFooter),t))},s=function(r){r.is(":checked")?o(e.overlayFooter).show():o(e.overlayFooter).hide()},i=function(e){o.post(ajaxurl,e.serializeArray(),function(e){o(".lp_navigation").showMessage(e)})},c=function(e,r){o(e).attr("style",r)},u=function(r){o("."+e.headerTitle).val(r.header_title).change(),o("."+e.headerColor).val(r.header_color).change(),o("."+e.headerBgColor).val(r.header_bg_color).change(),o("."+e.purchaseBgColor).val(r.main_bg_color).change(),o("."+e.purchaseMainText).val(r.main_text_color).change(),o("."+e.purchaseDescription).val(r.description_color).change(),o("."+e.buttonBgColor).val(r.button_bg_color).change(),o("."+e.buttonTextColor).val(r.button_text_color).change(),o("."+e.linkMainColor).val(r.link_main_color).change(),o("."+e.linkHoverColor).val(r.link_hover_color).change(),o("."+e.footerBgColor).val(r.footer_bg_color).change(),!0===r.show_footer?o("."+e.showFooter).attr("checked","checked"):o("."+e.showFooter).removeAttr("checked")},p=function(){r()};p()}e()})}(jQuery);