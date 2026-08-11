import { execFileSync } from 'node:child_process';

/**
 * Reads the real OTP value issued for `email` via the idp:get-latest-otp
 * artisan command - the mailer queues via Redis, so there is no catchable
 * local mailbox to read the code from instead.
 *
 * Runs the command directly when php/artisan are reachable in the current
 * process (CI, host dev against `php artisan serve`), or through
 * `docker exec idp-app` when running against the dockerized stack (APP_URL
 * points at the nginx service - see docker-compose.yml's playwright service).
 */
export function getLatestOtp(email: string): string {
  const dockerized = (process.env.APP_URL ?? '').includes('nginx');
  const [cmd, args] = dockerized
    ? ['docker', ['exec', 'idp-app', 'php', 'artisan', 'idp:get-latest-otp', email]]
    : ['php', ['artisan', 'idp:get-latest-otp', email]];

  return execFileSync(cmd, args, { encoding: 'utf-8' }).trim();
}
