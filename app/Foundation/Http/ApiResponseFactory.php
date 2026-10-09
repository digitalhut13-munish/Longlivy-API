<?php

namespace App\Foundation\Http;

use Illuminate\Routing\ResponseFactory;

/**
 * Default response factory for the API. Whole-number floats are
 * emitted with a trailing ".0" (JSON_PRESERVE_ZERO_FRACTION) so the
 * JSON numeric type is a float on any consumer, and so that strict
 * contract tests comparing decoded floats stay green.
 */
class ApiResponseFactory extends ResponseFactory
{
    /**
     * {@inheritDoc}
     */
    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        return parent::json(
            $data,
            $status,
            $headers,
            $options | JSON_PRESERVE_ZERO_FRACTION
        );
    }
}