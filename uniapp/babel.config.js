const plugins = []

// Support for nullish coalescing operator (??) and optional chaining (?.)
plugins.push('@babel/plugin-proposal-nullish-coalescing-operator')
plugins.push('@babel/plugin-proposal-optional-chaining')

module.exports = {
  presets: [
    '@vue/cli-plugin-babel/preset'
  ],
  plugins
}
