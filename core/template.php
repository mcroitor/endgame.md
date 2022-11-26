<?php
namespace core;

/*
 * The MIT License
 *
 * Copyright 2019 Croitor Mihail <mcroitor@gmail.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * simple template filler
 *
 * @author Croitor Mihail <mcroitor@gmail.com>
 */
class template {

    /**
     * template
     * @var string
     */
    protected string $template;
    protected array $modifiers = [
        "prefix" => "",
        "suffix" => ""
    ];

    /**
     *
     * @param string $template
     */
    public function __construct(string $template) {
        $this->template = $template;
    }

    public function set_prefix(string $prefix) {
        $this->modifiers["prefix"] = $prefix;
    }
    
    public function set_suffix(string $suffix) {
        $this->modifiers["suffix"] = $suffix;
    }

    /**
     * $data is a simple list of pairs <i>$pattern</i> => <i>$value</i>
     * Method replace <i>$pattern</i> with <i>$value</i>
     * and return new template object
     * Example:
     * <pre>$template->fill($data1)->fill(data2)->value();</pre>
     * @param array $data
     * @return \template
     */
    public function fill(array $data): template {
        $html = $this->template;
        foreach ($data as $pattern => $value) {
            $pattern = $this->modifiers["prefix"] . $pattern . $this->modifiers["suffix"];
            $html = str_replace($pattern, $value, $html);
        }
        return new template($html);
    }
    
    /**
     * Replace single $pattern with $value
     * @param string $pattern
     * @param string $value
     * @return \template
     */
    public function fill_value(string $pattern, string $value): template {
        return  new template(str_replace($pattern, $value, $this->template));
    }

    /**
     * returns template value
     * @return string
     */
    public function value(): string {
        return $this->template;
    }

}
