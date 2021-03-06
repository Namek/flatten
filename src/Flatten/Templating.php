<?php
namespace Flatten;

use Closure;
use Illuminate\Container\Container;

/**
 * Handles hooking into the various templating systems
 */
class Templating
{
	/**
	 * The IoC Container
	 *
	 * @var Container
	 */
	protected $app;

	/**
	 * Build a new Templating
	 *
	 * @param Container $app
	 */
	public function __construct(Container $app)
	{
		$this->app = $app;
	}

	////////////////////////////////////////////////////////////////////
	/////////////////////////////// ENGINES ////////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Register the templating tags with the various engines
	 *
	 * @return void
	 */
	public function registerTags()
	{
		$this->registerBlade();
	}

	/**
	 * Register the Flatten tags with Blade
	 *
	 * @return void
	 */
	public function registerBlade()
	{
		// Extend Blade
		$blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();
		$blade->extend(function($view, $blade) {

			// Replace opener
			$pattern = $blade->createOpenMatcher('cache');
			$replace = "<?php \$viewArgs = get_defined_vars(); echo Flatten\Facades\Flatten::section$2, function() use (\$viewArgs) { extract(\$viewArgs); ?>";
			$view    = preg_replace($pattern, $replace, $view);

			// Replace closing tag
			$view = str_replace('@endcache', '<?php }); ?>', $view);

			return $view;
		});
	}

	////////////////////////////////////////////////////////////////////
	////////////////////////////// SECTIONS ////////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Register a section to cache with Flatten
	 *
	 * @param  string  $name
	 * @param  integer $lifetime
	 * @param  Closure $contents
	 *
	 * @return void
	 */
	public function section($name, $lifetime, $contents = null)
	{
		// Variable arguments
		if (!$contents) {
			$contents = $lifetime;
			$lifetime = $this->app['flatten.cache']->getLifetime();
		}

		return $this->app['cache']->remember($this->formatSectionName($name), $lifetime, function() use ($contents) {
			ob_start();
				print $contents();
			return ob_get_clean();
		});
	}

	/**
	 * Flush a section in particular
	 *
	 * @param  string $name
	 *
	 * @return void
	 */
	public function flushSection($name)
	{
		return $this->app['cache']->forget($this->formatSectionName($name));
	}

	////////////////////////////////////////////////////////////////////
	/////////////////////////////// HELPERS ////////////////////////////
	////////////////////////////////////////////////////////////////////

	/**
	 * Format a section name
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function formatSectionName($name)
	{
		return 'flatten-section-'.$name;
	}
}