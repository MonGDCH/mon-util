<?php

namespace mon\util;

/**
 * 验证器
 *
 * @method ip 			验证IP地址
 * @method moble		验证手机号码
 * @method tel			验证固定电话号码
 * @method email 		验证邮箱
 * @method china 		验证中文
 * @method language 	验证字母数字
 * @method alpha 		验证字母
 * @method lower 		验证小写字母
 * @method upper 		验证大写字母
 * @method account  	验证账号，只允许字母、数字和下划线、破折号
 * @method id  			验证ID, 大于0的正整数
 * @method num 			验证数字
 * @method max 			验证最大值
 * @method min 			验证最小值
 * @method length 		验证长度
 * @method maxLength 	验证最大长度
 * @method minLength 	验证最小长度
 * @method required 	验证不能为空
 * @method date   		验证是否为一个有效的日期
 * @method timestamp   	验证是否为一个有效的时间戳
 * @method after 		验证最后日期
 * @method before 		验证最早日期
 * @method url 			验证URL
 * @method float 		验证浮点数
 * @method integer 		验证整数
 * @method regexp 		自定义正则验证
 * @method in 			相当于in_array
 * @method notIn 		相当于!in_array
 * @method str 			验证字符串
 * @method arr 			验证数组
 * @method json 		验证JSON
 * @method xml 			验证XML
 *
 * @author Mon <985558837@qq.com>
 * @version v1.3
 */
class Validate
{
	/**
	 * 对应待验证数据使用的验证规则
	 * [
	 * 	'name'	=> 'required|length:3'
	 * ]
	 * 
	 * @var array
	 */
	public $rule = [];

	/**
	 * 当前验证规则
	 *
	 * @var array
	 */
	public $checkRule = [];

	/**
	 * 待验证的数据
	 * [
	 * 	'name'	=> 'abc',
	 * 	'age'	=> '18'
	 * ]
	 * 
	 * @var array
	 */
	public $data = [];

	/**
	 * 错误提示
	 * [
	 * 	'name' => [
	 * 		'required'	=> '名称未设置',
	 * 		'length'	=> '长度错误'
	 * 	]
	 * ]
	 * 
	 * @var array
	 */
	public $message  = [];

	/**
	 * 验证的场景
	 *
	 * @var array
	 */
	public $scope = [];

	/**
	 * 当前校验的场景
	 *
	 * @var [type]
	 */
	public $checkScope = null;

	/**
	 * 正则匹配规则
	 *
	 * @var array
	 */
	protected $regex = [
		'ip'		=> '/((2[0-4]\d|25[0-5]|[01]?\d\d?)\.){3}(2[0-4]\d|25[0-5]|[01]?\d\d?)/',
		'moble'		=> '/^[1][3456789][0-9]{9}$/',
		'tel'		=> '/^(0\d{2,3}-\d{7,8})(-\d{1,4})?$/',
		'email'		=> '/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/',
		'china'		=> '/^[\x{4e00}-\x{9fa5}]+$/u',	// 中文
		'language'	=> '/^\w*$/',					// 英文数字
		'alpha'		=> '/^[A-Za-z]+$/',				// 只允许英文
		'account'	=> '/^[A-Za-z0-9\-\_]+$/',		// 只允许字母、数字和下划线 破折号
		'lower'		=> '/^[a-z]+$/',				// 小写字母
		'upper'		=> '/^[A-Z]+$/',				// 大写字母
	];

	/**
	 * 执行数据验证
	 *
	 * @param array $data	验证的数据
	 * @return void
	 */
	public function check(array $data = [])
	{
		if (!empty($data) && is_array($data)) {
			$this->data = array_merge($this->data, $data);
		}

		// 解析验证规则
		$errorItme = null;
		$checkRule = empty($this->checkRule) ? $this->rule : $this->checkRule;
		// 判断是否存在验证场景，获取验证字段
		if (!empty($this->checkScope) && is_array($this->checkScope)) {
			foreach ($this->checkScope as $v) {
				if (isset($checkRule[$v])) {
					$scopeRule[$v] = $checkRule[$v];
				}
			}
			$this->checkScope = null;
		} else {
			$scopeRule = $checkRule;
		}

		foreach ($scopeRule as $dataItem => $rules) {
			// 分割获取验证规则
			$rule = explode("|", $rules);
			// 存在节点，验证节点
			if (isset($this->data[$dataItem])) {
				$value = $this->data[$dataItem];
				// 解析规则
				$status = $this->analysis($value, $rule);
				if ($status !== true) {
					// 验证错误，返回[错误节点，错误规则]
					$errorItme = [$dataItem, $status];
					break;
				}
			} elseif (in_array('required', $rule)) {
				// 不存在节点，返回节点不存在
				$errorItme = [$dataItem, 'nofound'];
				break;
			}
		}

		// 判断是否存在错误节点
		if (!empty($errorItme)) {
			// 判断是否存在错误提示信息, 存在返回错误提示信息
			if (isset($this->message[$errorItme[0]])) {
				// 字符串，直接返回提示
				if (is_string($this->message[$errorItme[0]])) {
					return $this->message[$errorItme[0]];
				}
				// 数组，返回对应节点提示
				elseif (isset($this->message[$errorItme[0]][$errorItme[1]])) {
					return $this->message[$errorItme[0]][$errorItme[1]];
				} else {
					// 返回默认提示
					return $errorItme[0] . ' check Error';
				}
			} elseif ($errorItme[1] == 'nofound') {
				// 不存在节点
				return $errorItme[0] . ' is not found';
			} else {
				// 返回默认提示
				return $errorItme[0] . ' check Error';
			}
		} else {
			return true;
		}
	}

	/**
	 * 设置本次数据的验证规则
	 *
	 * @param array $rule	验证规则
	 * @return void
	 */
	public function rule(array $rule = [])
	{
		$this->checkRule = $rule;

		return $this;
	}

	/**
	 * 设置需要验证的数据
	 *
	 * @param array $data	验证的数据
	 * @return void
	 */
	public function data(array $data = [])
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * 设置错误提示信息
	 *
	 * @param array $message 错误信息
	 * @return [type]          [description]
	 */
	public function message($message = [])
	{
		$this->message = $message;

		return $this;
	}

	/**
	 * 设置校验场景
	 *
	 * @param string $item 查询场景名称
	 * @return [type]       [description]
	 */
	public function scope($item)
	{
		if (is_array($item)) {
			$this->checkScope = $item;
		}
		if (is_string($item) && isset($this->scope[$item])) {
			$this->checkScope = $this->scope[$item];
		}

		return $this;
	}

	###############################  辅助方法  ###################################

	/**
	 * 解析规则
	 *
	 * @param [type] $value	验证的值
	 * @param [type] $rule	对应的验证规则
	 * @return void
	 */
	protected function analysis($value, $rule)
	{
		$resule = true;
		foreach ($rule as $key => $type) {
			// 分割获取规则参数，支持二维。例子：max:9
			$item = explode(":", $type);
			if (count($item) > 1) {
				$status = $this->checkItem($value, $item[0], $item[1]);
			} else {
				$status = $this->checkItem($value, $item[0]);
			}

			// 判断验证是否通过,失败返回当前校验失败的规则名称
			if (!$status) {
				$resule = $item[0];
				break;
			}
		}

		return $resule;
	}

	/**
	 * 验证数据
	 *
	 * @param [type] $value		验证值
	 * @param [type] $rule		验证规则
	 * @param [type] $rule_data	规则参数
	 * @return void
	 */
	protected function checkItem($value, $rule, $rule_data = null)
	{
		if ($rule_data !== null) {
			$resule = call_user_func_array([$this, $rule], [$value, $rule_data]);
		} else {
			$resule = call_user_func_array([$this, $rule], [$value]);
		}

		return $resule;
	}

	/**
	 * 获取数组或字符串长度
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	protected function getLength($value)
	{
		if (is_array($value)) {
			$length = count($value);
		} else {
			$length = mb_strlen((string) $value, 'UTF-8');
		}

		return $length;
	}

	###############################  验证规则  ###################################

	/**
	 * 不能为空
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function required($value)
	{
		return !empty($value) || '0' == $value;
	}

	/**
	 * 最大值
	 *
	 * @param  [type] $value 操作的数据
	 * @param  [type] $max   验证的数据
	 * @return [type]        [description]
	 */
	public function max($value, $max)
	{
		return $this->num($value) && $value <= $max;
	}

	/**
	 * 最小值
	 *
	 * @param  [type] $value 操作的数据
	 * @param  [type] $min   验证的数据
	 * @return [type]        [description]
	 */
	public function min($value, $min)
	{
		return $this->num($value) && $value >= $min;
	}

	/**
	 * 指定长度(数组或字符串)
	 *
	 * @param  [type] $value  操作的数据
	 * @param  [type] $length 验证的数据
	 * @return [type]         [description]
	 */
	public function length($value, $length)
	{
		return $this->getLength($value) == $length;
	}

	/**
	 * 最大长度(数组或字符串)
	 *
	 * @param  [type] $value		操作的数据
	 * @param  [type] $maxLength	验证的数据
	 * @return [type]        		[description]
	 */
	public function maxLength($value, $maxLength)
	{
		return $this->getLength($value) <= $maxLength;
	}

	/**
	 * 最小长度(数组或字符串)
	 *
	 * @param  [type] $value     操作的数据
	 * @param  [type] $minLength 验证的数据
	 * @return [type]            [description]
	 */
	public function minLength($value, $minLength)
	{
		return $this->getLength($value) >= $minLength;
	}

	/**
	 * 验证是否为一个有效的日期
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function date($value)
	{
		return false !== strtotime($value);
	}

	/**
	 * 验证是否为一个有效的时间戳
	 *
	 * @param [type] $value	操作的数据
	 * @return void
	 */
	public function timestamp($value)
	{
		return $this->int($value) && (strtotime(date('Y-m-d H:i:s', $value)) === $value);
	}

	/**
	 * 最后日期
	 *
	 * @param  [type] $value 操作的数据
	 * @param  [type] $date  验证的数据
	 * @return [type]        [description]
	 */
	public function after($value, $date)
	{
		return ($this->date($value) && strtotime($value) >= strtotime($date));
	}

	/**
	 * 最早日期
	 *
	 * @param  [type] $value 操作的数据
	 * @param  [type] $date  验证的数据
	 * @return [type]        [description]
	 */
	public function before($value, $date)
	{
		return ($this->date($value) && strtotime($value) <= strtotime($date));
	}

	/**
	 * 正则验证
	 *
	 * @param  [type] $value  操作的数据
	 * @param  [type] $regexp 验证的数据
	 * @return [type]         [description]
	 */
	public function regexp($value, $regexp)
	{
		// 判断是否存在'/'，不存在则补上
		if (strpos($regexp, '/') !== 0) {
			// 不是正则表达式则两端补上/
			$regexp = '/^' . $regexp . '$/';
		}

		return preg_match($regexp, $value) === 1;
	}

	/**
	 * IP地址
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function ip($value)
	{
		return preg_match($this->regex['ip'], $value) === 1;
	}

	/**
	 * 手机号码
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function moble($value)
	{
		return preg_match($this->regex['moble'], $value) === 1;
	}

	/**
	 * 固定电话
	 *
	 * @param [type] $value 操作的数据
	 * @return void
	 */
	public function tel($value)
	{
		return preg_match($this->regex['tel'], $value) === 1;
	}

	/**
	 * 邮箱地址
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function email($value)
	{
		return preg_match($this->regex['email'], $value) === 1;
	}

	/**
	 * 中文，只支持UTF-8格式编码
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function china($value)
	{
		return preg_match($this->regex['china'], $value) === 1;
	}

	/**
	 * 字母和数字
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function language($value)
	{
		return preg_match($this->regex['language'], $value) === 1;
	}

	/**
	 * 字母
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function alpha($value)
	{
		return preg_match($this->regex['alpha'], $value) === 1;
	}

	/**
	 * 小写字母
	 *
	 * @param [type] $value 操作的数据
	 * @return void
	 */
	public function lower($value)
	{
		return preg_match($this->regex['lower'], $value) === 1;
	}

	/**
	 * 大写字母
	 *
	 * @param [type] $value 操作的数据
	 * @return void
	 */
	public function upper($value)
	{
		return preg_match($this->regex['upper'], $value) === 1;
	}

	/**
	 * 只允许字母、数字和下划线 破折号
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function account($value)
	{
		return preg_match($this->regex['account'], $value) === 1;
	}

	/**
	 * 大于0的正整数
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function id($value)
	{
		return $this->int($value) && ($value > 0);
	}

	/**
	 * 有效URL
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function url($value)
	{
		return false !== filter_var($value, FILTER_VALIDATE_URL);
	}

	/**
	 * 浮点数
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function float($value)
	{
		return false !== filter_var($value, FILTER_VALIDATE_FLOAT);
	}

	/**
	 * 整数
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function int($value)
	{
		return (is_numeric($value) && is_int($value + 0));
	}

	/**
	 * 数字
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function num($value)
	{
		return is_numeric($value);
	}

	/**
	 * 字符串
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function str($value)
	{
		return is_string($value);
	}

	/**
	 * 数组
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function arr($value)
	{
		return is_array($value);
	}

	/**
	 * JSON
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function json($value)
	{
		return $this->str($value) && !is_null(json_decode($value));
	}

	/**
	 * XML
	 *
	 * @param  [type] $value 操作的数据
	 * @return [type]        [description]
	 */
	public function xml($value)
	{
		$xmlParser = xml_parser_create();
		if (!$this->str($value) || !xml_parse($xmlParser, $value, true)) {
			xml_parser_free($xmlParser);
			return false;
		}

		return true;
	}

	/**
	 * 只允许某些值
	 *
	 * @param  [type] $value 操作的数据
	 * @param  [type] $in    验证的数据
	 * @return [type]        [description]
	 */
	public function in($value, $in)
	{
		return in_array($value, explode(',', $in));
	}

	/**
	 * 不允许某些值
	 *
	 * @param  [type] $value 操作的数据
	 * @param  [type] $notin 验证的数据
	 * @return [type]        [description]
	 */
	public function notIn($value, $notin)
	{
		return !in_array($value, explode(',', $notin));
	}
}
