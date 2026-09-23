import { emailValidator, buildPasswordValidationSchema } from '../../../resources/js/validator';

describe('emailValidator', () => {
  it('accepts valid emails', () => {
    expect(emailValidator('user@example.com')).toBe(true);
    expect(emailValidator('test@test.com')).toBe(true);
    expect(emailValidator('user+tag@sub.domain.org')).toBe(true);
  });

  it('rejects invalid emails', () => {
    expect(emailValidator('notanemail')).toBe(false);
    expect(emailValidator('@nodomain')).toBe(false);
    expect(emailValidator('spaces in@email.com')).toBe(false);
    expect(emailValidator('')).toBe(false);
  });
});

describe('buildPasswordValidationSchema', () => {
  const policy = {
    min_length: 8,
    max_length: 64,
    shape_pattern: '^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$]).+$',
    allowed_special_characters: '[a-zA-Z0-9!@#$]',
    shape_warning: 'Password must have uppercase, number and special character',
  };

  it('returns schema with password and password_confirmation fields', () => {
    const schema = buildPasswordValidationSchema(policy);
    expect(schema).toHaveProperty('password');
    expect(schema).toHaveProperty('password_confirmation');
  });

  it('password schema rejects values shorter than min_length', async () => {
    const schema = buildPasswordValidationSchema(policy, true);
    await expect(schema.password.validate('Ab1!')).rejects.toThrow();
  });

  it('password schema rejects values longer than max_length', async () => {
    const longPass = 'A'.repeat(65) + '1!';
    const schema = buildPasswordValidationSchema(policy);
    await expect(schema.password.validate(longPass)).rejects.toThrow();
  });
});
