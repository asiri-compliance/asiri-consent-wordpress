// Runs assets/bridge.js against a stub window and checks the category mapping and the consent type.
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const assert = require('node:assert')

const src = fs.readFileSync(path.join(__dirname, '../asiri-consent/assets/bridge.js'), 'utf8')

function run(region, serverType) {
  const calls = []
  const events = []
  let listener = null
  const win = {
    asiriConsentBridge: { type: serverType },
    asiri: {
      region,
      ready: false,
      categories: {},
      onChange: (fn) => {
        listener = fn
        return () => {}
      },
    },
    wp_set_consent: (c, v) => calls.push([c, v]),
    CustomEvent: function (name) {
      this.type = name
    },
    setTimeout: (fn) => fn,
    document: { dispatchEvent: (e) => events.push(e.type) },
  }
  win.window = win
  vm.runInNewContext(src, win)
  return { win, calls, events, fire: (s) => listener(s) }
}

// Mapping
let r = run('NG', 'optin')
assert.strictEqual(r.win.wp_consent_type, 'optin')
assert.deepStrictEqual(r.events, ['wp_consent_type_defined'])
r.fire({
  categories: { necessary: true, preferences: false, analytics: true, marketing: false },
  region: 'NG',
})
assert.deepStrictEqual(r.calls, [
  ['functional', 'allow'],
  ['preferences', 'deny'],
  ['statistics', 'allow'],
  ['statistics-anonymous', 'allow'],
  ['marketing', 'deny'],
])

// Reject then accept: every category follows
r = run('NG', 'optin')
r.fire({
  categories: { necessary: true, preferences: false, analytics: false, marketing: false },
  region: 'NG',
})
assert.deepStrictEqual(
  r.calls.filter((c) => c[1] === 'allow'),
  [['functional', 'allow']],
)
r.calls.length = 0
r.fire({
  categories: { necessary: true, preferences: true, analytics: true, marketing: true },
  region: 'NG',
})
assert.strictEqual(r.calls.filter((c) => c[1] === 'allow').length, 5)

// A category not in the state is left alone
r = run('NG', 'optin')
r.fire({ categories: { marketing: true }, region: 'NG' })
assert.deepStrictEqual(r.calls, [['marketing', 'allow']])

// Consent type by region
assert.strictEqual(run('US-CA', 'optin').win.wp_consent_type, 'optout')
assert.strictEqual(run('GB', 'optout').win.wp_consent_type, 'optin')
// No region known: the server's answer
assert.strictEqual(run('', 'optout').win.wp_consent_type, 'optout')
