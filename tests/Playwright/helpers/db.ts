import { spawn } from 'node:child_process';

const PROJECT_ROOT = new URL('../../../', import.meta.url).pathname;

/**
 * Run a SQL statement against the project's DDEV MariaDB. The SQL is piped over stdin so
 * shell metacharacters (backticks, $, …) inside the query are not re-interpreted.
 */
export async function ddevMysql(sql: string): Promise<string> {
  return await new Promise((resolve, reject) => {
    const child = spawn('ddev', ['exec', 'mysql', '-udb', '-pdb', 'db', '-N', '-B'], {
      cwd: PROJECT_ROOT,
    });
    let stdout = '';
    let stderr = '';
    child.stdout.on('data', (chunk) => (stdout += chunk.toString()));
    child.stderr.on('data', (chunk) => (stderr += chunk.toString()));
    child.on('error', reject);
    child.on('close', (code) => {
      if (code === 0) resolve(stdout.trim());
      else reject(new Error(`ddev mysql exited ${code}: ${stderr.trim() || stdout.trim()}`));
    });
    child.stdin.write(sql);
    child.stdin.end();
  });
}

export async function rowExists(sql: string): Promise<boolean> {
  const out = await ddevMysql(sql);
  return out.length > 0 && out !== '0';
}
