<?php

namespace Craft;


class Customlogviewer_CustomLogEntryModel extends LogEntryModel
{
    protected function defineAttributes()
    {
        $attributes = parent::defineAttributes();
        $attributes['title'] = AttributeType::String;
        $attributes['channel'] = AttributeType::String;
        $attributes['stacktrace'] = AttributeType::Mixed;
        return $attributes;
    }
}