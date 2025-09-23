import {rollup} from 'rollup';
import {globSync} from "glob";
import fs from "fs";
import autoprefixer from "autoprefixer";
import * as sass from "sass";
import postcss from "postcss";
import path from "path";
import {WebSocket} from 'ws';

import {loadConfigFile} from 'rollup/loadConfigFile';

// read all scss files
const scssFiles = globSync('assets/scss/*.scss', {ignore: 'node_modules/**'});

console.log('Building assets started');

// load the config file next to the current script;
// the provided config object has the same effect as passing "--format es"
// on the command line and will override the format of all outputs
loadConfigFile(path.resolve('rollup.dev.config.mjs'), {
    format: 'es'
}).then(async ({ options, warnings }) => {
    // "warnings" wraps the default `onwarn` handler passed by the CLI.
    // This prints all warnings up to this point:
    console.log(`We currently have ${warnings.count} warnings`);

    // This prints all deferred warnings
    warnings.flush();

    // options is an array of "inputOptions" objects with an additional
    // "output" property that contains an array of "outputOptions".
    // The following will generate all outputs for all inputs, and write
    // them to disk the same way the CLI does it:
    for (const optionsObj of options) {
        const bundle = await rollup(optionsObj);
        await Promise.all(optionsObj.output.map(bundle.write));
    }

    // You can also pass this directly to "rollup.watch"
    // rollup.watch(options);
});


for (const file of scssFiles) {
    // transform sass to css
    const result = sass.compile(file, {style: 'compressed'});

    // get filename without extension
    const filename = path.parse(file).name;

    // early return if no css is available
    if (!result.css) {
        continue;
    }

    // handle css by postcss and autoprefixer
    await postcss([autoprefixer])
        .process(result.css, {
            from: `public/css/${filename}.css`,
            to: `public/css/${filename}.min.css`
        })
        .then(result => {
            // write css file to file system
            fs.writeFileSync(`public/css/${filename}.min.css`, result.css);
        })
        .finally(async () => {
            // send websocket message to websocket server
            let client = new WebSocket('ws://localhost:8999');

            // client.on('message', msg => console.log('Message from server:', msg));

            // Wait for the client to connect using async/await
            await new Promise(resolve => client.once('open', resolve));

            // Prints "Hello!" twice, once for each client.
            client.send('reload');

            client.close();

            client = null;
        });
}
