module.exports = {
  testEnvironment: 'jsdom',
  testMatch: ['<rootDir>/tests/js/**/*.test.js'],
  // @marsidev/react-turnstile ships as pure ESM; allow Babel to transform it.
  transformIgnorePatterns: ['/node_modules/(?!@marsidev/react-turnstile)'],
  moduleNameMapper: {
    '\\.(css|scss|sass|less)$': 'identity-obj-proxy',
    '\\.(jpg|jpeg|png|gif|svg|ttf|woff|woff2|eot|otf|webp)$':
      '<rootDir>/tests/js/__mocks__/fileMock.js',
  },
  setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js'],
  transform: {
    '^.+\\.[jt]sx?$': 'babel-jest',
  },
  moduleDirectories: ['node_modules', 'resources/js'],
  collectCoverageFrom: ['resources/js/**/*.{js,jsx}', '!resources/js/index.js'],
  coverageDirectory: 'tests/js/coverage',
};
