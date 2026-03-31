const path = require('path')

module.exports = {
  transpileDependencies: ['@dcloudio/uni-ui'],
  configureWebpack: {
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './')
      }
    }
  },
  chainWebpack: (config) => {
    // 处理字体文件
    config.module
      .rule('fonts')
      .test(/\.(woff2?|eot|ttf|otf)(\?.*)?$/)
      .use('url-loader')
      .loader('url-loader')
      .options({
        limit: 10000,
        name: 'static/fonts/[name].[hash:8].[ext]'
      })
    
    // 处理图片文件
    config.module
      .rule('images')
      .test(/\.(png|jpe?g|gif|webp|svg)(\?.*)?$/)
      .use('url-loader')
      .loader('url-loader')
      .options({
        limit: 10000,
        name: 'static/img/[name].[hash:8].[ext]'
      })
  }
}
