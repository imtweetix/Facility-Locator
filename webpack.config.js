const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  const isAnalyze = process.env.ANALYZE === 'true';

  const config = {
    entry: {
      'admin': './src/admin/js/main.js',
      'public': './src/public/js/main.js',
      'admin-style': './src/admin/css/main.scss',
      'public-style': './src/public/css/main.scss'
    },

    output: {
      path: path.resolve(__dirname, 'dist'),
      filename: (pathData) => {
        // CSS files will be handled by MiniCssExtractPlugin
        if (pathData.chunk.name.includes('-style')) {
          return '[name].js'; // Temporary, will be removed by CleanWebpackPlugin
        }
        return `[name]/js/facility-locator-[name]${isProduction ? '.min' : ''}.js`;
      },
      clean: true
    },

    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: [
                ['@babel/preset-env', {
                  targets: {
                    browsers: ['> 1%', 'last 2 versions', 'not dead', 'not ie 11']
                  }
                }]
              ]
            }
          }
        },
        {
          test: /\.(scss|css)$/,
          use: [
            MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                sourceMap: !isProduction,
                importLoaders: 2
              }
            },
            {
              loader: 'postcss-loader',
              options: {
                sourceMap: !isProduction,
                postcssOptions: {
                  plugins: [
                    require('autoprefixer'),
                    ...(isProduction ? [require('cssnano')({ preset: 'default' })] : [])
                  ]
                }
              }
            },
            {
              loader: 'sass-loader',
              options: {
                sourceMap: !isProduction,
                sassOptions: {
                  outputStyle: isProduction ? 'compressed' : 'expanded'
                }
              }
            }
          ]
        },
        {
          test: /\.(png|jpe?g|gif|svg)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'assets/images/[name][ext]'
          }
        },
        {
          test: /\.(woff|woff2|eot|ttf|otf)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'assets/fonts/[name][ext]'
          }
        }
      ]
    },

    plugins: [
      new CleanWebpackPlugin(),

      new MiniCssExtractPlugin({
        filename: (pathData) => {
          const name = pathData.chunk.name.replace('-style', '');
          return `${name}/css/facility-locator-${name}${isProduction ? '.min' : ''}.css`;
        }
      }),

      ...(isAnalyze ? [new BundleAnalyzerPlugin()] : [])
    ],

    optimization: {
      minimize: isProduction,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            format: {
              comments: false,
            },
            compress: {
              drop_console: isProduction,
              drop_debugger: isProduction
            }
          },
          extractComments: false,
        }),
      ],
      splitChunks: {
        cacheGroups: {
          vendor: {
            test: /[\\/]node_modules[\\/]/,
            name: 'vendors',
            chunks: 'all',
            filename: 'vendors/js/vendors.min.js'
          }
        }
      }
    },

    resolve: {
      extensions: ['.js', '.jsx', '.scss', '.css'],
      alias: {
        '@admin': path.resolve(__dirname, 'src/admin'),
        '@public': path.resolve(__dirname, 'src/public'),
        '@shared': path.resolve(__dirname, 'src/shared'),
        '@assets': path.resolve(__dirname, 'assets')
      }
    },

    devtool: isProduction ? false : 'source-map',

    stats: {
      colors: true,
      modules: false,
      children: false,
      chunks: false,
      chunkModules: false
    },

    performance: {
      hints: isProduction ? 'warning' : false,
      maxEntrypointSize: 250000,
      maxAssetSize: 250000
    }
  };

  return config;
};