<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class TransformsRequest
{
    /**
     * The additional attributes passed to the middleware.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request       Request
     * @param \Closure                 $next          Next Closure
     * @param mixed                    ...$attributes Attributes
     * @return mixed
     */
    public function handle($request, Closure $next, ...$attributes)
    {
        $this->attributes = $attributes;

        $this->clean($request);

        return $next($request);
    }

    /**
     * Clean the request's data.
     *
     * @param  \Illuminate\Http\Request $request Request
     * @return void
     */
    protected function clean($request)
    {
        $this->cleanParameterBag($request->query);

        if ($request->isJson()) {
            $this->cleanParameterBag($request->json());
        } else {
            $this->cleanParameterBag($request->request);
        }
    }

    /**
     * Clean the data in the parameter bag.
     *
     * @param  \Symfony\Component\HttpFoundation\ParameterBag $bag Bag
     * @return void
     */
    protected function cleanParameterBag(ParameterBag $bag)
    {
        $bag->replace($this->cleanArray($bag->all()));
    }

    /**
     * Clean the data in the given array.
     *
     * @param  array $data Data
     * @return array
     */
    protected function cleanArray(array $data)
    {
        return collect($data)->map(
            function ($value, $key) {
                return $this->cleanValue($key, $value);
            }
        )->all();
    }

    /**
     * Clean the given value.
     *
     * @param  string $key   Key
     * @param  mixed  $value Value
     * @return mixed
     */
    protected function cleanValue($key, $value)
    {
        if (is_array($value)) {
            return $this->cleanArray($value);
        }

        return $this->transform($key, $value);
    }

    /**
     * Transform the given value.
     *
     * @param  string $key   Key
     * @param  mixed  $value Value
     * @return mixed
     */
    abstract protected function transform($key, $value);
}
