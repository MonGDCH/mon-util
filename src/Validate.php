<?php

declare(strict_types=1);

namespace mon\util;

use mon\util\exception\ValidateException;

/**
 * 验证器
 *
 * @author Mon <985558837@qq.com>
 * @version 2.0.0	优化重构代码
 */
class Validate
{
	/**
	 * 对应待验证数据使用的验证规则
	 * [
	 * 	'name'	=> ['required', 'length:3']
	 * ]
	 * 
	 * @var array
	 */
	protected $rule = [];

	/**
	 * 当前验证规则
	 *
	 * @var array
	 */
	protected $checkRule = [];

	/**
	 * 待验证的数据
	 * [
	 * 	'name'	=> 'abc',
	 * 	'age'	=> '18'
	 * ]
	 * 
	 * @var array
	 */
	protected $data = [];

	/**
	 * 当前验证的数据
	 *
	 * @var array
	 */
	protected $checkData = [];

	/**
	 * 错误提示
	 * [
	 * 	'name' => [
	 * 		'required'	=> '名称未设置',
	 * 		'length'	=> '长度错误'
	 * 	],
	 *  'age' => '请输入年龄'
	 * ]
	 * 
	 * @var array
	 */
	protected $message  = [];

	/**
	 * 当前验证错误信息数据
	 *
	 * @var array
	 */
	protected $checkMessage = [];

	/**
	 * 验证的场景
	 *
	 * @var array
	 */
	protected $scope = [];

	/**
	 * 当前校验的场景
	 *
	 * @var mixed
	 */
	protected $checkScope = [];

	/**
	 * 错误信息
	 *
	 * @var mixed
	 */
	protected $error = null;

	/**
	 * 正则匹配规则
	 *
	 * @var array
	 */
	protected $regex = [
		// 手机号
		'mobile'	=> '/^[1][3456789][0-9]{9}$/',
		// 电话号码
		'tel'		=> '/^(0\d{2,3}-\d{7,8})(-\d{1,4})?$/',
		// 中文
		'china'		=> '/^[\x{4e00}-\x{9fa5}]+$/u',
		// 英文数字
		'language'	=> '/^\w*$/',
		// 只允许英文
		'alpha'		=> '/^[A-Za-z]+$/',
		// 只允许字母、数字、下划线和破折号
		'account'	=> '/^[A-Za-z0-9\-\_]+$/',
		// 小写字母
		'lower'		=> '/^[a-z]+$/',
		// 大写字母
		'upper'		=> '/^[A-Z]+$/',
		// 营业执照
		'license'	=> '/^[a-zA-Z0-9]{10,20}$/',
		// 银行卡号
		'pay_card'	=> '/^[1-9]\d{9,29}$/',
		// 车牌号
		'car_num'	=> '/^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领][A-HJ-NP-Z][A-HJ-NP-Z0-9]{4,5}[A-HJ-NP-Z0-9挂学警港澳]$/',
		// 邮政编码
		'postal'		=> '/\d{6}/',
	];

	/**
	 * 执行数据验证
	 *
	 * @param array $data	验证的数据
	 * @return boolean
	 */
	public function check(array $data = []): bool
	{
		// 错误节点
		$errorItme = [];
		// 验证数据
		$checkData = array_merge($this->getCheckData(), $data);
		// 验证错误信息
		$checkMessage = $this->getCheckMessage();
		// 获取验证规则
		$scopeRule = $this->getCheckScopeRule();

		// 验证数据
		foreach ($scopeRule as $dataItem => $rules) {
			// 分割获取验证规则
			$rule = is_array($rules) ? $rules : explode('|', $rules);
			// 存在节点，验证节点
			if (isset($checkData[$dataItem])) {
				$value = $checkData[$dataItem];
				// 解析规则
				$status = $this->analysis($value, $rule, $dataItem, $checkData);
				if ($status !== true) {
					// 验证错误，返回[错误节点，错误规则]
					$errorItme = [$dataItem, $status];
					break;
				}
			} elseif (in_array('required', $rule)) {
				// 不存在节点，返回节点不存在
				$errorItme = [$dataItem, 'required'];
				break;
			} else if (in_array('isset', $rule)) {
				// 不存在节点，返回节点不存在
				$errorItme = [$dataItem, 'isset'];
				break;
			}
		}

		// 重置验证数据
		$this->reset();

		// 不存在错误节点，验证通过
		if (empty($errorItme)) {
			return true;
		}
		// 存在错误提示信息, 返回错误提示信息
		if (isset($checkMessage[$errorItme[0]])) {
			if (is_string($checkMessage[$errorItme[0]])) {
				// 字符串，直接返回提示
				$this->error = $checkMessage[$errorItme[0]];
				return false;
			} elseif (isset($checkMessage[$errorItme[0]][$errorItme[1]])) {
				// 数组，返回对应节点提示
				$this->error = $checkMessage[$errorItme[0]][$errorItme[1]];
				return false;
			} else {
				// 返回默认提示
				$this->error = $errorItme[0] . ' check error';
				return false;
			}
		}
		// 返回默认提示
		$this->error = $errorItme[0] . ' check faild';
		return false;
	}

	/**
	 * check方法的异常处理封装，当验证不通过是抛出异常
	 *
	 * @param array $data
	 * @throws ValidateException
	 * @return true
	 */
	public function checked(array $data = []): bool
	{
		$check = $this->check($data);
		if (!$check) {
			throw new ValidateException($this->getError(), 500);
		}

		return true;
	}

	/**
	 * 设置需要验证的数据
	 *
	 * @param array $data	验证的数据
	 * @return Validate
	 */
	public function data(array $data = []): Validate
	{
		$this->checkData = $data;

		return $this;
	}

	/**
	 * 获取默认的验证数据
	 *
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * 验证当前验证的数据
	 *
	 * @return array
	 */
	public function getCheckData(): array
	{
		return array_merge($this->data, $this->checkData);
	}

	/**
	 * 设置当前的验证规则
	 *
	 * @param array $rule	验证规则
	 * @return Validate
	 */
	public function rule(array $rule = []): Validate
	{
		$this->checkRule = $rule;

		return $this;
	}

	/**
	 * 获取验证规则
	 *
	 * @return array
	 */
	public function getRule(): array
	{
		return $this->rule;
	}

	/**
	 * 获取当前的验证规则
	 *
	 * @return array
	 */
	public function getCheckRule(): array
	{
		return empty($this->checkRule) ? $this->rule : $this->checkRule;
	}

	/**
	 * 设置错误提示信息
	 *
	 * @param array $message 错误信息
	 * @return Validate
	 */
	public function message(array $message = []): Validate
	{
		$this->checkMessage = $message;

		return $this;
	}

	/**
	 * 获取错误提示信息
	 *
	 * @return array
	 */
	public function getMessage(): array
	{
		return $this->message;
	}

	/**
	 * 获取当前的验证错误信息
	 *
	 * @return array
	 */
	public function getCheckMessage(): array
	{
		return empty($this->checkMessage) ? $this->message : $this->checkMessage;
	}

	/**
	 * 设置校验场景
	 *
	 * @param string|array $item 查询场景名称
	 * @return Validate
	 */
	public function scope($item): Validate
	{
		if (is_array($item)) {
			$this->checkScope = $item;
		} else if (is_string($item) && isset($this->scope[$item])) {
			$this->checkScope = $this->scope[$item];
		} else {
			throw new ValidateException('未支持的验证场景');
		}

		return $this;
	}

	/**
	 * 验证验证场景
	 *
	 * @return array
	 */
	public function getScope(): array
	{
		return $this->scope;
	}

	/**
	 * 获取当前验证场景验证规则
	 *
	 * @return array
	 */
	public function getCheckScopeRule(): array
	{
		$scopeRule = [];
		$checkRule = $this->getCheckRule();
		if (!empty($this->checkScope) && is_array($this->checkScope)) {
			foreach ($this->checkScope as $v) {
				if (isset($checkRule[$v])) {
					$scopeRule[$v] = $checkRule[$v];
				}
			}
		} else {
			$scopeRule = $checkRule;
		}

		return $scopeRule;
	}

	/**
	 * 重置验证器数据
	 *
	 * @return Validate
	 */
	public function reset(): Validate
	{
		$this->checkData = [];
		$this->checkRule = [];
		$this->checkMessage = [];
		$this->checkScope = [];
		return $this;
	}

	/**
	 * 获取错误信息
	 *
	 * @return mixed
	 */
	public function getError()
	{
		$error = $this->error;
		$this->error = null;
		return $error;
	}

	###############################  辅助方法  ###################################

	/**
	 * 解析规则
	 *
	 * @param mixed $value	验证的值
	 * @param array $rule	对应的验证规则
	 * @param string $dataItem 验证的字段名
	 * @param array $checkData 验证的数据
	 * @return true|string 成功返回true,失败返回验证失败的规则名称
	 */
	protected function analysis($value, array $rule, string $dataItem, array $checkData)
	{
		$resule = true;
		foreach ($rule as $type) {
			// 分割获取规则参数，支持二维。例子：max:9
			$item = explode(':', $type, 2);
			if (count($item) > 1) {
				$status = $this->checkItem($dataItem, $value, $checkData, $item[0], $item[1]);
			} else {
				$status = $this->checkItem($dataItem, $value, $checkData, $item[0]);
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
	 * @param array $checkData  验证的数据
	 * @param string $rule		验证规则
	 * @param mixed $rule_data	规则参数
	 * @return boolean
	 */
	protected function checkItem(string $field, $value, array $checkData, string $rule, $rule_data = null): bool
	{
		// 过滤不需要验证的规则
		if (in_array($rule, ['isset'])) {
			return true;
		}

		$resule = false;
		if (method_exists($this, $rule)) {
			$options = is_null($rule_data) ? [$value, $checkData, $field] : [$value, $rule_data, $checkData, $field];
			$resule = call_user_func_array([$this, $rule], $options);
		}

		return $resule;
	}

	/**
	 * 获取数组或字符串长度
	 *
	 * @param  mixed $value 操作的数据
	 * @return integer
	 */
	protected function getLength($value): int
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
	public function required($value): bool
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
	public function max($value, $max): bool
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
	public function min($value, $min): bool
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
	public function length($value, $length): bool
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
	public function maxLength($value, $maxLength): bool
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
	public function minLength($value, $minLength): bool
	{
		return $this->getLength($value) >= $minLength;
	}

	/**
	 * 验证是否为一个有效的日期
	 *
	 * @param  mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function date($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return strtotime($value) !== false;
	}

	/**
	 * 验证是否为一个有效的时间戳
	 *
	 * @param mixed $value	操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function timestamp($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return $this->int($value) && (strtotime(date('Y-m-d H:i:s', intval($value))) == $value);
	}

	/**
	 * 最后日期
	 *
	 * @param  mixed $value 操作的数据
	 * @param  mixed $date  验证的数据
	 * @return boolean
	 */
	public function afterDate($value, $date): bool
	{
		return ($this->date($value) && strtotime(strval($value)) >= strtotime(strval($date)));
	}

	/**
	 * 最早日期
	 *
	 * @param  mixed $value 操作的数据
	 * @param  mixed $date  验证的数据
	 * @return boolean
	 */
	public function beforeDate($value, $date): bool
	{
		return ($this->date($value) && strtotime(strval($value)) <= strtotime(strval($date)));
	}

	/**
	 * 正则验证
	 *
	 * @param  mixed $value  操作的数据
	 * @param  mixed $regexp 验证的数据
	 * @return boolean
	 */
	public function regexp($value, $regexp): bool
	{
		// 判断是否存在'/'，不存在则补上
		if (mb_strpos($regexp, '/') !== 0) {
			// 不是正则表达式则两端补上/
			$regexp = '/^' . $regexp . '$/';
		}

		return preg_match($regexp, strval($value)) === 1;
	}

	/**
	 * IP地址
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function ip($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return filter_var($value, FILTER_VALIDATE_IP) !== false;
	}

	/**
	 * 内网IP地址
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function intranet($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return !filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
	}

	/**
	 * mac地址
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function mac($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return filter_var($value, FILTER_VALIDATE_MAC) !== false;
	}

	/**
	 * 手机号码
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function mobile($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['mobile'], strval($value)) === 1;
	}

	/**
	 * 固定电话
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function tel($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['tel'], strval($value)) === 1;
	}

	/**
	 * 邮政编码
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function postal($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['postal'], strval($value)) === 1;
	}

	/**
	 * 邮箱地址
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function email($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * 中文，只支持UTF-8格式编码
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function china($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['china'], strval($value)) === 1;
	}

	/**
	 * 字母和数字
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function language($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['language'], strval($value)) === 1;
	}

	/**
	 * 字母
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function alpha($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['alpha'], strval($value)) === 1;
	}

	/**
	 * 小写字母
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function lower($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['lower'], strval($value)) === 1;
	}

	/**
	 * 大写字母
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function upper($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['upper'], strval($value)) === 1;
	}

	/**
	 * 只允许字母、数字和下划线 破折号
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function account($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['account'], strval($value)) === 1;
	}

	/**
	 * 验证营业执照
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function license($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['license'], strval($value)) === 1;
	}

	/**
	 * 验证银行卡号
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function payCard($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['pay_card'], strval($value)) === 1;
	}

	/**
	 * 验证车牌号
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function carNum($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return preg_match($this->regex['car_num'], strval($value)) === 1;
	}

	/**
	 * 大于0的正整数
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function id($value): bool
	{
		return $this->int($value) && ($value > 0);
	}

	/**
	 * 有效URL
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function url($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return filter_var($value, FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * 域名domain
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function domain($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return filter_var($value, FILTER_VALIDATE_DOMAIN) !== false;
	}

	/**
	 * 浮点数
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function float($value): bool
	{
		return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
	}

	/**
	 * 整数
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function int($value): bool
	{
		return filter_var($value, FILTER_VALIDATE_INT) !== false;
	}

	/**
	 * 数字
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function num($value): bool
	{
		return is_numeric($value);
	}

	/**
	 * 字符串
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function str($value): bool
	{
		return is_string($value);
	}

	/**
	 * 数组
	 *
	 * @param  mixed $value 操作的数据
	 * @return boolean
	 */
	public function array($value): bool
	{
		return is_array($value);
	}

	/**
	 * JSON
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function json($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		if (function_exists('Json_validate')) {
			return Json_validate(strval($value));
		}
		return $this->str($value) && !is_null(json_decode($value));
	}

	/**
	 * XML
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function xml($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		$xmlParser = xml_parser_create();
		if (!$this->str($value) || !xml_parse($xmlParser, strval($value), true)) {
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
	public function in($value, $in): bool
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
	public function notIn($value, $notin): bool
	{
		$notin = is_string($notin) ? explode(',', $notin) : $notin;
		return !in_array($value, $notin);
	}

	/**
	 * 值是否在某个区间
	 *
	 * @param integer|float $value	比较值
	 * @param string $rule	验证规则
	 * @return boolean
	 */
	public function between($value, $rule): bool
	{
		[$min, $max] = is_string($rule) ? explode(',', $rule) : $rule;

		return $value >= $min && $value <= $max;
	}

	/**
	 * 值是否不在某个区间
	 *
	 * @param integer|float $value	比较值
	 * @param string $rule	验证规则
	 * @return boolean
	 */
	public function notBetween($value, $rule): bool
	{
		[$min, $max] = is_string($rule) ? explode(',', $rule) : $rule;

		return $value < $min || $value > $max;
	}

	/**
	 * 身份证号码(支持15位和18位)
	 *
	 * @param mixed $value 操作的数据
	 * @param boolean $allowEmpty 是否允许为空字符串或null
	 * @return boolean
	 */
	public function idCard($value, $allowEmpty = false): bool
	{
		if ($allowEmpty && ($value === '' || is_null($value))) {
			return true;
		}
		return IdCard::instance()->check($value);
	}

	/**
	 * 比较字段值是否一致
	 *
	 * @param mixed $value	比较的值
	 * @param string $rule	比较的字段名
	 * @param array $data	验证的数据
	 * @return boolean
	 */
	public function confirm($value, $rule, array $data = []): bool
	{
		return isset($data[$rule]) && $value == $data[$rule];
	}

	/**
	 * 比较字段值是否不一致
	 *
	 * @param mixed $value	比较的值
	 * @param string $rule	比较的字段名
	 * @param array $data	验证的数据
	 * @return boolean
	 */
	public function different($value, $rule, array $data = []): bool
	{
		return !isset($data[$rule]) || $value != $data[$rule];
	}

	/**
	 * 是否以某个字符串开头
	 *
	 * @param mixed $value 字段值
	 * @param string $rule  验证规则
	 * @return boolean
	 */
	public function startWith($value, string $rule): bool
	{
		if (!is_string($value)) {
			return false;
		}
		if (function_exists('str_starts_with')) {
			return str_starts_with($value, $rule);
		}
		$code = substr($value, 0, 1);
		return $code == $rule;
	}

	/**
	 * 是否以某个字符串结尾
	 * 
	 * @param mixed $value 字段值
	 * @param string $rule  验证规则
	 * @return boolean
	 */
	public function endWith($value, string $rule): bool
	{
		if (!is_string($value)) {
			return false;
		}
		if (function_exists('str_ends_with')) {
			return str_ends_with($value, $rule);
		}
		$code = substr($value, -1);
		return $code == $rule;
	}

	/**
	 * 是否包含某个字符串
	 *
	 * @param mixed $value 字段值
	 * @param string $rule  验证规则
	 * @return boolean
	 */
	public function contain($value, string $rule): bool
	{
		if (!is_string($value)) {
			return false;
		}
		if (function_exists('str_contains')) {
			return str_contains($value, $rule);
		}
		return strpos($value, $rule) !== false;
	}

	/**
	 * 比较两值是否不相等
	 *
	 * @param mixed $value	比较的值1
	 * @param mixed $rule	比较的值2
	 * @return boolean
	 */
	public function neq($value, $rule): bool
	{
		return $value != $rule;
	}

	/**
	 * 比较两值是否相等
	 *
	 * @param mixed $value	比较的值1
	 * @param mixed $rule	比较的值2
	 * @return boolean
	 */
	public function eq($value, $rule): bool
	{
		return $value == $rule;
	}

	/**
	 * 判断值是否为金额
	 *
	 * @param mixed $value	操作的数据
	 * @return boolean
	 */
	public function money($value): bool
	{
		return $this->num($value) && $this->int($value * 100) && $value >= 0;
	}

	/**
	 * 字符串列表验证
	 * 
	 * value: 1,2,4,5,6
	 * scope: listCheck:required,id
	 *
	 * @param mixed $value  验证值
	 * @param string $rule  验证规则
	 * @throws ValidateException
	 * @return boolean
	 */
	public function listCheck($value, $rule): bool
	{
		if (!$this->required($value)) {
			return true;
		}
		$rules = explode(',', $rule);
		$values = explode(',', $value);
		foreach ($rules as $call) {
			if (!is_string($call) || empty($call) || !method_exists($this, $call)) {
				throw new ValidateException('验证器验证方法不支持[' . $call . ']');
			}
			foreach ($values as $v) {
				if (!call_user_func([$this, $call], $v)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * 字符串列表长度验证
	 *
	 * @param mixed $value	验证值
	 * @param integer $count 长度
	 * @return boolean
	 */
	public function listCount($value, $count): bool
	{
		$length = $value ? count(explode(',', $value)) : 0;
		return $length == $count;
	}

	/**
	 * 字符串列表最大长度验证
	 *
	 * @param mixed $value	验证值
	 * @param integer $count 长度
	 * @return boolean
	 */
	public function listMaxCount($value, $count): bool
	{
		$length = $value ? count(explode(',', $value)) : 0;
		return $length <= $count;
	}

	/**
	 * 字符串列表最小长度验证
	 *
	 * @param mixed $value	验证值
	 * @param integer $count 长度
	 * @return boolean
	 */
	public function listMinCount($value, $count): bool
	{
		$length = $value ? count(explode(',', $value)) : 0;
		return $length >= $count;
	}

	/**
	 * 验证某个字段必须是指定值的倍数
	 *
	 * @param mixed $value 字段值
	 * @param mixed $rule 比较倍数值
	 * @return bool
	 */
	public function multipleOf($value, $rule): bool
	{
		if (!$this->num($value)) {
			return false;
		}
		if (!$this->num($rule)) {
			throw new ValidateException('比较倍数值必须为数字');
		}
		if ('0' == $rule || $value < $rule) {
			return false;
		}

		return $value % $rule === 0;
	}
}
