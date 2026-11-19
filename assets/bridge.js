/*
 * Asiri Consent: the browser half of the WP Consent API bridge.
 * Plain ES2019, no dependencies. Sets the consent type, then reports every change of choice to
 * wp_set_consent(). It reads choices from window.asiri and never stores or sends anything itself.
 */
;(function (win) {
  'use strict'

  // Asiri category -> WP Consent API categories.
  var MAP = {
    necessary: ['functional'],
    preferences: ['preferences'],
    analytics: ['statistics', 'statistics-anonymous'],
    marketing: ['marketing'],
  }

  var OPT_OUT_REGIONS = ['US-CA', 'US-SIGNAL']
  var doc = win.document
  var typeSet = false

  function typeFor(region) {
    return OPT_OUT_REGIONS.indexOf(region) !== -1 ? 'optout' : 'optin'
  }

  function announce(type) {
    win.wp_consent_type = type
    if (typeof win.CustomEvent === 'function') {
      doc.dispatchEvent(new win.CustomEvent('wp_consent_type_defined'))
    } else {
      var ev = doc.createEvent('Event')
      ev.initEvent('wp_consent_type_defined', true, true)
      doc.dispatchEvent(ev)
    }
    typeSet = true
  }

  function apply(categories) {
    if (typeof win.wp_set_consent !== 'function') return
    Object.keys(MAP).forEach(function (name) {
      if (!Object.prototype.hasOwnProperty.call(categories, name)) return
      var value = categories[name] === true ? 'allow' : 'deny'
      MAP[name].forEach(function (wpCategory) {
        win.wp_set_consent(wpCategory, value)
      })
    })
  }

  function start(asiri) {
    var region = typeof asiri.region === 'string' ? asiri.region : ''
    announce(region ? typeFor(region) : serverType())
    asiri.onChange(function (state) {
      var next = state && state.region ? typeFor(state.region) : null
      if (next && next !== win.wp_consent_type) announce(next)
      apply((state && state.categories) || {})
    })
    // A choice already made on an earlier page is reported straight away.
    if (asiri.ready) apply(asiri.categories || {})
  }

  function serverType() {
    var s = win.asiriConsentBridge
    return s && s.type === 'optout' ? 'optout' : 'optin'
  }

  // The script may load after this file; wait for it for up to ten seconds. Until then the server's answer stands.
  function waitFor(tries) {
    var asiri = win.asiri
    if (asiri && typeof asiri.onChange === 'function') return start(asiri)
    if (!typeSet) announce(serverType())
    if (tries > 0) win.setTimeout(function () { waitFor(tries - 1) }, 250)
  }

  waitFor(40)
})(window)
