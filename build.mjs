import {globSync} from 'glob';
import fs from 'node:fs';
import path from 'node:path';
import autoprefixer from 'autoprefixer';
import * as sass from 'sass';
import postcss from 'postcss';

fs.mkdirSync('public/css', {recursive: true});
fs.mkdirSync('public/js', {recursive: true});
for (const file of globSync('assets/scss/cookie-consent.scss')) {
    const result = sass.compile(file, {style: 'compressed'});
    const target = `public/css/${path.parse(file).name}.min.css`;
    const css = await postcss([autoprefixer]).process(result.css, {from: file, to: target});
    fs.writeFileSync(target, css.css);
}
