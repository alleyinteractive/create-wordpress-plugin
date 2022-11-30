/** @type {import('ts-jest/dist/types').InitialOptionsTsJest} */
module.exports = {
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/$1',
  },
  modulePathIgnorePatterns: [
    '.buddy-tests',
  ],
  testEnvironment: 'node',
};
