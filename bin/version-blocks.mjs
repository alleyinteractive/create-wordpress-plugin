/**
 * Sets each built block's `version` to a hash of every other file the build wrote for it.
 *
 * WordPress versions a block's assets by the `version` in its metadata, and the source block.json
 * files all say 0.1.0, so browsers and caches would keep serving old styles after a deploy. Run
 * after the build and before `wp-scripts build-blocks-manifest`, which copies the versions into the
 * manifest WordPress registers the blocks from.
 *
 * Usage: node bin/version-blocks.mjs [build directory, default "build"]
 */
import { createHash } from 'node:crypto';
import {
  existsSync, readdirSync, readFileSync, writeFileSync,
} from 'node:fs';
import { join } from 'node:path';

const buildDir = process.argv[2] ?? 'build';

readdirSync(buildDir, { withFileTypes: true })
  .filter((entry) => entry.isDirectory() && existsSync(join(buildDir, entry.name, 'block.json')))
  .forEach(({ name }) => {
    const blockDir = join(buildDir, name);
    const hash = createHash('sha256');

    readdirSync(blockDir, { withFileTypes: true })
      .filter((entry) => entry.isFile() && entry.name !== 'block.json')
      .map((entry) => entry.name)
      .sort()
      .forEach((file) => hash.update(file).update(readFileSync(join(blockDir, file))));

    const blockJsonPath = join(blockDir, 'block.json');
    const blockJson = JSON.parse(readFileSync(blockJsonPath, 'utf8'));
    blockJson.version = hash.digest('hex').slice(0, 20);
    writeFileSync(blockJsonPath, JSON.stringify(blockJson, null, 2));
  });
