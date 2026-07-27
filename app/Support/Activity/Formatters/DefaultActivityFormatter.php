<?php

namespace App\Support\Activity\Formatters;

class DefaultActivityFormatter extends BaseActivityFormatter
{
    // Fallback for log names without a dedicated formatter — no field
    // labels of its own, keys are humanized as-is by the base class.
}
