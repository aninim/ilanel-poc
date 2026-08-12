/**
 * Check the two seeders agree on the behaviours that matter.
 *
 * They drifted once already (Task 1 updated only the blueprint generator),
 * and the failure was invisible: both ran without error, they just produced
 * different products. This asserts the specific calls rather than diffing
 * the files, which are legitimately different shapes.
 */
const fs = require('fs');

const REPO = require('path').resolve(__dirname, '..');
const cli = fs.readFileSync(REPO + '/scripts/seed-products.php', 'utf8');
const gen = fs.readFileSync(REPO + '/scripts/build-playground-blueprint.js', 'utf8');

const CHECKS = [
  ['creates variable products', /new WC_Product_Variable\(\)/],
  ['rebuilds simple as variable', /new WC_Product_Variable\(\s*\$\w*[eE]xisting(->ID|\[.ID.\])/],
  ['local attributes set_id(0)', /set_id\(\s*0\s*\)/],
  ['marks attribute for variation', /set_variation\(\s*true\s*\)/],
  ['creates variations', /new WC_Product_Variation\(\)/],
  ['sets variation price', /set_regular_price\(\s*\(string\)\s*\$\w+\[.price.\]/],
  ['deletes old children', /get_children\(\)/],
  ['syncs parent', /WC_Product_Variable::sync\(/],
  ['seeds projects', /'post_type'\s*=>\s*'project'/],
  ['links product to project', /ILANEL_Projects::link\(/],
  // Both must prefer the real SKU and treat DEMO-* as a fallback only.
  ['prefers real sku', /0 !== strpos\(\s*\$\w+\[.sku.\], 'PLACEHOLDER'/],
];

let fail = 0;

console.log('behaviour'.padEnd(32), 'CLI'.padStart(5), 'BLUEPRINT'.padStart(10));
console.log('-'.repeat(50));

CHECKS.forEach(([label, re]) => {
  const a = re.test(cli);
  const b = re.test(gen);
  const agree = a === b;
  if (!agree) fail++;
  console.log(
    label.padEnd(32),
    (a ? 'yes' : 'no').padStart(5),
    (b ? 'yes' : 'no').padStart(10),
    agree ? '' : '  <-- DIVERGENT'
  );
});

console.log('-'.repeat(50));
console.log(fail === 0 ? 'PARITY OK' : `${fail} DIVERGENCE(S)`);
process.exit(fail === 0 ? 0 : 1);
