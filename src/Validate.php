<?php

namespace mon\util;

use mon\util\exception\ValidateException;

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
 * @method idCard		验证身份证号
 * @method confirm		比较字段
 * @method eq	 		比较值
 *
 * @author Mon <985558837@qq.com>
 * @version v1.3.3	2021-04-26 优化代码，增加getError获取错误信息，check方法返回固定boolean值
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
	 * @var mixed
	 */
	public $checkScope = null;

	/**
	 * 错误信息
	 *
	 * @var mixed
	 */
	public $error = null;

	/**
	 * 正则匹配规则
	 *
	 * @var array
	 */
	protected $regex = [
		'moble'		=> '/^[1][3456789][0-9]{9}$/',
		'tel'		=> '/^(0\d{2,3}-\d{7,8})(-\d{1,4})?$/',
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
	 * @return boolean
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
			$rule = is_array($rules) ? $rules : explode("|", $rules);
			// 存在节点，验证节点
			if (isset($this->data[$dataItem])) {
				$value = $this->data[$dataItem];
				// 解析规则
				$status = $this->analysis($value, $rule, $dataItem);
				if ($status !== true) {
					// 验证错误，返回[错误节点，错误规则]
					$errorItme = [$dataItem, $status];
					break;
				}
			} elseif (in_array('required', $rule)) {
				// 不存在节点，返回节点不存在
				$errorItme = [$dataItem, 'required'];
				break;
			}
		}

		// 判断是否存在错误节点
		if (!empty($errorItme)) {
			// 判断是否存在错误提示信息, 存在返回错误提示信息
			if (isset($this->message[$errorItme[0]])) {
				if (is_string($this->message[$errorItme[0]])) {
					// 字符串，直接返回提示
					$this->error = $this->message[$errorItme[0]];
					return false;
				} elseif (isset($this->message[$errorItme[0]][$errorItme[1]])) {
					// 数组，返回对应节点提示
					$this->error = $this->message[$errorItme[0]][$errorItme[1]];
					return false;
				} else {
					// 返回默认提示
					$this->error = $errorItme[0] . ' check error';
					return false;
				}
			} else {
				// 返回默认提示
				$this->error = $errorItme[0] . ' check faild';
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * check方法的异常处理封装，当验证不通过是抛出异常
	 *
	 * @param array $data
	 * @throws ValidateException
	 * @return true
	 */
	public function checked(array $data = [])
	{
		$check = $this->check($data);
		if (!$check !== true) {
			throw new ValidateException($this->getError(), 500);
		}

		return true;
	}

	/**
	 * 设置本次数据的验证规则
	 *
	 * @param array $rule	验证规则
	 * @return Validate
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
	 * @return Validate
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
	 * @return Validate
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
	 * @return Validate
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

	/**
	 * 获取错误信息
	 *
	 * @return mixed
	 */
	public function getError()
	{
		return $this->error;
	}

	###############################  辅助方法  ###################################

	/**
	 * 解析规则
	 *
	 * @param mixed $value	验证的值
	 * @param mixed $rule	对应的验证规则
	 * @param string $dataItem 验证的字段名
	 * @return mixed 成功返回true,失败返回验证失败的规则名称
	 */
	protected function analysis($value, $rule, $dataItem)
	{
		$resule = true;
		foreach ($rule as $key => $type) {
			// 分割获取规则参数，支持二维。例子：max:9
			$item = explode(":", $type, 2);
			if (count($item) > 1) {
				$status = $this->checkItem($dataItem, $value, $item[0], $item[1]);
			} else {
				$status = $this->checkItem($dataItem, $value, $item[0]);
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
	 * @param string $field		验证字段名称
	 * @param mixed $value		验证值
	 * @param string $rule		验证规则
	 * @param mixed $rule_data	规则参数
	 * @return boolean
	 */
	protected function checkItem($field, $value, $rule, $rule_data = null)
	{
		if (!is_null($rule_data)) {
			$resule = call_user_func_array([$this, $rule], [$value, $rule_data, $field]);
		} else {
			$resule = call_user_func_array([$this, $rule], [$value, $field]);
		}

		return $resule;
	}

	/**
	 * 获取数组或字符串长度
	 *
	 * @param  mixed $value 操作的数据
	 * @return integer
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
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function required($value)
	{
		return !empty($value) || '0' == $value;
	}

	/**
	 * 最大值
	 *
	 * @param  mixed $value 操作的数据
	 * @param  mixed $max   验证的数据
	 * @return boolean
	 */
	public function max($value, $max)
	{
		return $this->num($value) && $value <= $max;
	}

	/**
	 * 最小值
	 *
	 * @param  mixed $value 操作的数据
	 * @param  mixed $min   验证的数据
	 * @return boolean
	 */
	public function min($value, $min)
	{
		return $this->num($value) && $value >= $min;
	}

	/**
	 * 指定长度(数组或字符串)
	 *
	 * @param  mixed $value  操作的数据
	 * @param  mixed $length 验证的数据
	 * @return boolean
	 */
	public function length($value, $length)
	{
		return $this->getLength($value) == $length;
	}

	/**
	 * 最大长度(数组或字符串)
	 *
	 * @param  mixed $value		操作的数据
	 * @param  mixed $maxLength	验证的数据
	 * @return boolean
	 */
	public function maxLength($value, $maxLength)
	{
		return $this->getLength($value) <= $maxLength;
	}

	/**
	 * 最小长度(数组或字符串)
	 *
	 * @param  mixed $value     操作的数据
	 * @param  mixed $minLength 验证的数据
	 * @return boolean
	 */
	public function minLength($value, $minLength)
	{
		return $this->getLength($value) >= $minLength;
	}

	/**
	 * 验证是否为一个有效的日期
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function date($value)
	{
		return strtotime($value) !== false;
	}

	/**
	 * 验证是否为一个有效的时间戳
	 *
	 * @param mixed $value	操作的数据
	 * @return boolean
	 */
	public function timestamp($value)
	{
		return $this->int($value) && (strtotime(date('Y-m-d H:i:s', $value)) === $value);
	}

	/**
	 * 最后日期
	 *
	 * @param  mixed $value 操作的数据
	 * @param  mixed $date  验证的数据
	 * @return boolean
	 */
	public function afterDate($value, $date)
	{
		return ($this->date($value) && strtotime($value) >= strtotime($date));
	}

	/**
	 * 最早日期
	 *
	 * @param  mixed $value 操作的数据
	 * @param  mixed $date  验证的数据
	 * @return boolean
	 */
	public function beforeDate($value, $date)
	{
		return ($this->date($value) && strtotime($value) <= strtotime($date));
	}

	/**
	 * 正则验证
	 *
	 * @param  mixed $value  操作的数据
	 * @param  mixed $regexp 验证的数据
	 * @return boolean
	 */
	public function regexp($value, $regexp)
	{
		// 判断是否存在'/'，不存在则补上
		if (mb_strpos($regexp, '/') !== 0) {
			// 不是正则表达式则两端补上/
			$regexp = '/^' . $regexp . '$/';
		}

		return preg_match($regexp, $value) === 1;
	}

	/**
	 * IP地址
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function ip($value)
	{
		return filter_var($value, FILTER_VALIDATE_IP) !== false;
	}

	/**
	 * 手机号码
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function moble($value)
	{
		return preg_match($this->regex['moble'], $value) === 1;
	}

	/**
	 * 固定电话
	 *
	 * @param mixed $value 操作的数据
	 * @return boolean
	 */
	public function tel($value)
	{
		return preg_match($this->regex['tel'], $value) === 1;
	}

	/**
	 * 邮箱地址
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function email($value)
	{
		return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * 中文，只支持UTF-8格式编码
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function china($value)
	{
		return preg_match($this->regex['china'], $value) === 1;
	}

	/**
	 * 字母和数字
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function language($value)
	{
		return preg_match($this->regex['language'], $value) === 1;
	}

	/**
	 * 字母
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function alpha($value)
	{
		return preg_match($this->regex['alpha'], $value) === 1;
	}

	/**
	 * 小写字母
	 *
	 * @param mixed $value 操作的数据
	 * @return boolean
	 */
	public function lower($value)
	{
		return preg_match($this->regex['lower'], $value) === 1;
	}

	/**
	 * 大写字母
	 *
	 * @param mixed $value 操作的数据
	 * @return boolean
	 */
	public function upper($value)
	{
		return preg_match($this->regex['upper'], $value) === 1;
	}

	/**
	 * 只允许字母、数字和下划线 破折号
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function account($value)
	{
		return preg_match($this->regex['account'], $value) === 1;
	}

	/**
	 * 大于0的正整数
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function id($value)
	{
		return $this->int($value) && ($value > 0);
	}

	/**
	 * 有效URL
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function url($value)
	{
		return filter_var($value, FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * 浮点数
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function float($value)
	{
		return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
	}

	/**
	 * 整数
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function int($value)
	{
		return (is_numeric($value) && is_int($value + 0));
	}

	/**
	 * 数字
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function num($value)
	{
		return is_numeric($value);
	}

	/**
	 * 字符串
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function str($value)
	{
		return is_string($value);
	}

	/**
	 * 数组
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function arr($value)
	{
		return is_array($value);
	}

	/**
	 * JSON
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function json($value)
	{
		return $this->str($value) && !is_null(json_decode($value));
	}

	/**
	 * XML
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
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
	 * @param  mixed $value 操作的数据
	 * @param  string|array $in    验证的数据
	 * @return boolean
	 */
	public function in($value, $in)
	{
		$in = is_string($in) ? explode(',', $in) : $in;
		return in_array($value, $in);
	}

	/**
	 * 不允许某些值
	 *
	 * @param  mixed $value 操作的数据
	 * @param  string|array $notin 验证的数据
	 * @return boolean
	 */
	public function notIn($value, $notin)
	{
		$notin = is_string($notin) ? explode(',', $notin) : $notin;
		return !in_array($value, $notin);
	}

	/**
	 * 身份证号码(支持15位和18位)
	 *
	 * @param string $idcard 身份证号
	 * @return boolean
	 */
	public function idCard($idcard)
	{
		return IdCard::instance()->check($idcard);
	}

	/**
	 * 比较字段值是否一致
	 *
	 * @param mixed $value	比较的值
	 * @param string $rule	比较的字段名
	 * @param string $field	当前的字段名
	 * @param array $data	用于比较的数据集，默认为$this->data
	 * @return boolean
	 */
	public function confirm($value, $rule, $field = null, $data = [])
	{
		$data = empty($data) ? $this->data : $data;

		return !(!isset($data[$rule]) || $value != $data[$rule]);
	}

	/**
	 * 比较两值是否相等
	 *
	 * @param mixed $value	比较的值1
	 * @param mixed $rule	比较的值2
	 * @return boolean
	 */
	public function eq($value, $rule)
	{
		return $value == $rule;
	}
}
