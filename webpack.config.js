import * as path from 'path';
import * as fs from 'fs';
import { pathToFileURL } from 'url';

export default async (env) => {
  const logCyan = (s) => console.log('\x1b[36m%s\x1b[0m', s);
  const logYellow = (s) => console.log('\x1b[33m%s\x1b[0m', s);
  const logGreen = (s) => console.log('\x1b[32m%s\x1b[0m', s);
  const logRed = (s) => console.log('\x1b[31m%s\x1b[0m', s);

  logCyan('HUBLETO ASSETS COMPILER');
  logCyan('');

  let loaderFullPath = path.resolve(process.cwd(), env.loader ?? 'assets.config.js');
  if (!fs.existsSync(loaderFullPath)) {
    loaderFullPath = path.resolve(process.cwd(), 'vendor', 'hubleto', 'assets', 'assets.config.js');
  }
  logYellow('CONFIG');
  logYellow(loaderFullPath);

  const loader = await import(pathToFileURL(loaderFullPath).href);

  const { entries, outputPath, aliases, modules } = loader.init();

  logYellow('');
  logYellow('ENTRIES');
  logYellow(Object.keys(entries).join(', '));
  logYellow('');
  logYellow('OUTPUT_PATH');
  logYellow(outputPath);
  logYellow('');
  logYellow('');

  return {
    entry: entries,
    output: {
      path: outputPath,
      publicPath: '/assets/',
      filename: '[name].js',
      chunkFilename: '[name].js',
    },
    module: {
      rules: [
        {
          test: /\.(js|mjs|jsx|ts|tsx)$/,
          use: 'babel-loader',
        },
        {
          test: /\.(scss|css)$/,
          use: ['style-loader', 'css-loader', 'sass-loader'],
        },
        {
          test: /\.m?js$/,
          include: /node_modules[\\/]@uiw/,
          resolve: {
            fullySpecified: false,
          },
        },
      ],
    },
    optimization: {
      runtimeChunk: 'single',
      splitChunks: {
        cacheGroups: {
          modules: { test: /[\\/]node_modules[\\/]/, name: 'modules', chunks: 'all', enforce: true },
          reactUi: { test: /[\\/]hubleto[\\/]src[\\/]react-ui[\\/]/, name: 'react-ui', chunks: 'all', enforce: true },
        },
      },
    },
    resolve: {
      modules: ['node_modules', ...(modules ?? [])],
      extensions: ['.js', '.jsx', '.ts', '.tsx', '.scss', '.css'],
      alias: aliases,
    }
  };
}