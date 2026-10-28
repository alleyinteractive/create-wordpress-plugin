import { execFileSync } from 'child_process';
import {
  mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync,
} from 'fs';
import { tmpdir } from 'os';
import { join } from 'path';

const SCRIPT = join(__dirname, 'version-blocks.mjs');

describe('version-blocks', () => {
  let buildDir: string;

  /**
   * Writes a built block with the given files beside its block.json.
   */
  function writeBlock(name: string, files: Record<string, string>): void {
    mkdirSync(join(buildDir, name));
    const blockJson = { name: `create-wordpress-plugin/${name}`, version: '0.1.0' };
    writeFileSync(join(buildDir, name, 'block.json'), JSON.stringify(blockJson));
    Object.entries(files).forEach(([file, contents]) => {
      writeFileSync(join(buildDir, name, file), contents);
    });
  }

  /**
   * Runs the script and returns the version it gave a block.
   */
  function versionAfterRun(name: string): string {
    execFileSync(process.execPath, [SCRIPT, buildDir]);
    return JSON.parse(readFileSync(join(buildDir, name, 'block.json'), 'utf8')).version;
  }

  beforeEach(() => {
    buildDir = mkdtempSync(join(tmpdir(), 'version-blocks-'));
  });

  afterEach(() => {
    rmSync(buildDir, { recursive: true, force: true });
  });

  it('versions a block by its built files', () => {
    writeBlock('a', { 'index.js': 'one', 'style-index.css': 'two' });
    writeBlock('b', { 'index.js': 'one', 'style-index.css': 'two' });
    writeBlock('c', { 'index.js': 'one', 'style-index.css': 'three' });

    const version = versionAfterRun('a');

    expect(version).toMatch(/^[0-9a-f]{20}$/);
    expect(versionAfterRun('b')).toBe(version);
    expect(versionAfterRun('c')).not.toBe(version);
  });

  it('changes the version when only the PHP or a template changes', () => {
    writeBlock('a', { 'index.js': 'one', 'render.php': '<?php' });
    const version = versionAfterRun('a');

    writeFileSync(join(buildDir, 'a', 'render.php'), '<?php echo 1;');
    const afterPhp = versionAfterRun('a');
    writeFileSync(join(buildDir, 'a', 'template.twig'), '{{ title }}');

    expect(afterPhp).not.toBe(version);
    expect(versionAfterRun('a')).not.toBe(afterPhp);
  });

  it('gives the same version on a second run', () => {
    writeBlock('a', { 'index.js': 'one' });

    expect(versionAfterRun('a')).toBe(versionAfterRun('a'));
  });

  it('skips directories without a block.json', () => {
    mkdirSync(join(buildDir, 'entry'));
    writeFileSync(join(buildDir, 'entry', 'index.js'), 'one');

    expect(() => execFileSync(process.execPath, [SCRIPT, buildDir])).not.toThrow();
  });
});
