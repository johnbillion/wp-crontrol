const path = require('path');

module.exports = {
	mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
	entry: {
		'wp-crontrol': ['./js/wp-crontrol.js', './js/index.tsx'],
	},
	output: {
		path: path.resolve(__dirname, 'build'),
		filename: '[name].js',
		clean: true,
	},
	externals: {
		'react': 'React',
		'react-dom': 'ReactDOM',
		'react/jsx-runtime': 'ReactJSXRuntime',
		'@wordpress/i18n': ['wp', 'i18n'],
		'@wordpress/element': ['wp', 'element'],
	},
	module: {
		rules: [
			{
				test: /\.(ts|tsx)$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							'@babel/preset-env',
							['@babel/preset-react', { runtime: 'automatic' }],
							'@babel/preset-typescript',
						],
					},
				},
			},
		],
	},
	resolve: {
		extensions: ['.ts', '.tsx', '.js', '.jsx'],
	},
	plugins: [],
	devtool: process.env.NODE_ENV === 'production' ? false : 'eval-source-map',
};
