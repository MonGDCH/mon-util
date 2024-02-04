<?php

declare(strict_types=1);

namespace mon\util;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * PHP文档解析
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class DocParse
{
    use Instance;

    /**
     * 解析类型对象, 获取类型注解文档
     *
     * @param string $class 对象名称
     * @param integer $type ReflectionMethod对应的方法访问类型，默认Public
     * @throws RuntimeException
     * @return array
     */
    public function parseClass(string $class, $type = ReflectionMethod::IS_PUBLIC): array
    {
        if (class_exists($class)) {
            $result = [];
            $reflection = new ReflectionClass($class);
            $method = $reflection->getMethods($type);
            // 解析文档中所有的方法
            foreach ($method as $action) {
                $doc = $action->getDocComment();
                $data = $this->parse($doc);
                $result[$action->name] = $data;
            }

            return $result;
        }

        throw new RuntimeException('Class ' . $class . ' not exists');
    }

    /**
     * 解析文档，获取文档内容
     *
     * @param string $doc 注解文档内容
     * @return array
     */
    public function parse(string $doc): array
    {
        // 解析注解文本块，获取文档内容
        if (preg_match('#^/\*\*(.*)\*/#s', $doc, $comment) === false) {
            return [];
        }
        $comment = trim($comment[1]);
        // 获取所有行并去除第一个 * 字符
        if (preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false) {
            return [];
        }
        // 解析每行注解，获取对应的内容信息
        $result = $this->parseLines($lines[1]);
        return $result;
    }

    /**
     * 遍历解析注解信息，整理获取注解内容
     *
     * @param array $lines  注解内容
     * @return array
     */
    protected function parseLines(array $lines): array
    {
        $result = [];
        $description = [];
        foreach ($lines as $line) {
            $lineData = $this->parseLine($line);
            if (is_string($lineData)) {
                $description[] = $lineData;
            } else if (is_array($lineData)) {
                $result[$lineData['type']][] = $lineData['data'];
            }
        }
        $result['description'] = implode(PHP_EOL, $description);
        return $result;
    }

    /**
     * 逐行解析注解信息，获取注解内容
     *
     * @param string $line  行信息
     * @return array|string
     */
    protected function parseLine(string $line)
    {
        $content = trim($line);
        if (mb_strpos($content, '@') === 0) {
            if (mb_strpos($content, ' ') > 0) {
                // 获取参数名称
                $param = mb_substr($content, 1, mb_strpos($content, ' ') - 1);
                // 获取值
                $value = mb_substr($content, mb_strlen($param, 'UTF-8') + 2);
            } else {
                $param = mb_substr($content, 1);
                $value = '';
            }

            // 解析行参数
            switch ($param) {
                case 'param':
                    $value = $this->formatParam($value);
                    break;
                case 'return':
                case 'throws':
                    $value = $this->formatResult($value);
                    break;
            }
            return [
                'type' => $param,
                'data' => $value
            ];
        }

        return $content;
    }

    /**
     * 解析return或throws类型的参数
     *
     * @param string $string  注解字符串
     * @return array|string
     */
    protected function formatResult(string $string)
    {
        $string = trim($string);
        if (mb_strpos($string, ' ') !== false) {
            $data = explode(' ', $string, 3);
            $type = $data[0];
            $desc = isset($data[1]) ? $data[1] : '';

            return [
                'type' => $type,
                'name' => '',
                'desc' => trim($desc)
            ];
        }

        return $string;
    }

    /**
     * 解析param类型的参数
     *
     * @param string $string  注解字符串
     * @return string|array
     */
    protected function formatParam(string $string)
    {
        $string = trim($string);
        if (mb_strpos($string, ' ') !== false) {
            $data = explode(' ', $string, 3);
            $type = $data[0];
            if (count($data) > 1) {
                $name = $data[1];
                $desc = $data[2];
            } else {
                $name = $data[1];
            }

            return [
                'type' => $type,
                'name' => $name,
                'desc' => isset($desc) ? trim($desc) : ''
            ];
        }

        return $string;
    }
}
