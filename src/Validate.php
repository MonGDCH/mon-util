<?php
namespace Laf\plug;

/**
 * 验证器
 *
 * @method ip 			验证IP地址
 * @method moble		验证手机号码
 * @method email 		验证邮箱
 * @method china 		验证中文
 * @method language 	验证字母数字
 * @method alpha 		验证字符
 * @method account  	验证账号，只允许字母、数字和下划线 破折号
 * @method id  			验证ID, 大于0的正整数
 * @method num 			验证数字
 * @method max 			验证最大值
 * @method min 			验证最小值
 * @method length 		验证长度
 * @method maxLength 	验证最大长度
 * @method minLength 	验证最小长度
 * @method required 	验证不能为空
 * @method date   		验证是否为一个有效的日期
 * @method after 		验证最后日期
 * @method before 		验证最早日期
 * @method url 			验证URL
 * @method float 		验证浮点数
 * @method integer 		验证整数
 * @method regexp 		自定义正则验证
 * @method in 			相当于in_array
 * @method notIn 		相当于!in_array
 *
 * @author Mon <985558837@qq.com>
 * @version v1.1
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
		'email'		=> '/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/',
		'china'		=> '/^[\x{4e00}-\x{9fa5}]+$/u',	// 中文
		'language'	=> '/^\w*$/',					// 英文数字
		'alpha'		=> '/^[A-Za-z]+$/',				// 只允许英文
		'account'	=> '/^[A-Za-z0-9\-\_]+$/',		// 只允许字母、数字和下划线 破折号
	];

	/**
	 * 执行数据验证
	 *
	 * @return [type] [description]
	 */
	public function check(array $data = [])
	{
		if(!empty($data) && is_array($data)){
			$this->data = array_merge($this->data, $data);
		}

		// 解析验证规则
		$errorItme = null;
		// 判断是否存在验证场景，获取验证字段
		if(!empty($this->checkScope) && is_array($this->checkScope)){
			foreach($this->checkScope as $v)
			{
				if(isset($this->rule[$v])){
					$checkrule[$v] = $this->rule[$v];
				}
			}
			$this->checkScope = null;
		}
		else{
			$checkrule = $this->rule;
		}

		foreach($checkrule as $dataItem => $rules)
		{
			// 存在节点，验证节点
			if(isset($this->data[$dataItem])){
				// 分割获取验证规则
				$rule = explode("|", $rules);
				$value= $this->data[$dataItem];
				// 解析规则
				$status = $this->analysis($value, $rule);
				if($status !== true){
					// 验证错误，返回[错误节点，错误规则]
					$errorItme = [$dataItem, $status];
					break;
				}
			}
			else{
				// 不存在节点，返回节点不存在
				$errorItme = [$dataItem, 'nofound'];
				break;
			}
		}

		// 判断是否存在错误节点
		if(!empty($errorItme)){
			// 判断是否存在错误提示信息, 存在返回错误提示信息
			if(isset($this->message[$errorItme[0]])){
				// 字符串，直接返回提示
				if(is_string($this->message[ $errorItme[0] ])){
					return $this->message[ $errorItme[0] ];
				}
				// 数组，返回对应节点提示
				elseif(isset( $this->message[ $errorItme[0] ][ $errorItme[1] ] )){
					return $this->message[ $errorItme[0] ][ $errorItme[1] ];
				}
				else{
					// 返回默认提示
					return $errorItme[0] . ' check Error';
				}
			}
			elseif( $errorItme[1] == 'nofound' ){
				// 不存在节点
				return $errorItme[0].' is not found';
			}
			else{
				// 返回默认提示
				return $errorItme[0] . ' check Error';
			}
		}
		else{
			return true;
		}
	}

	/**
	 * 设置本次数据的验证规则
	 *
	 * @return [type] [description]
	 */
	public function rule(array $rule = [])
	{
		$this->rule = $rule;

		return $this;
	}

	/**
	 * 设置需要验证的数据
	 *
	 * @param array $data [description]
	 */
	public function data(array $data = [])
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * 设置错误提示信息
	 *
	 * @param  array  $message [description]
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
	 * @param  [type] $item [description]
	 * @return [type]       [description]
	 */
	public function scope($item)
	{
		if(is_array($item)){
			$this->checkScope = $item;
		}
		if(is_string($item) && isset($this->scope[$item])){
			$this->checkScope = $this->scope[$item];
		}

		return $this;
	}


	###############################  辅助方法  ###################################

	/**
	 * 解析规则
	 *
	 * @param  [type] $value [description]
	 * @param  [type] $rule  [description]
	 * @return [type]        [description]
	 */
	protected function analysis($value, $rule)
	{
		$resule = true;
		foreach($rule as $key => $type)
		{
			// 分割获取规则参数，支持二维。例子：max:9
			$item = explode(":", $type);
			if(count($item) > 1){
				$status = $this->checkItem($value, $item[0], $item[1]);
			}
			else{
				$status = $this->checkItem($value, $item[0]);
			}

			// 判断验证是否通过,失败返回当前校验失败的规则名称
			if(!$status){
				$resule = $item[0];
				break;
			}
		}

		return $resule;
	}

	/**
	 * 验证数据
	 *
	 * @return [type] [description]
	 */
	protected function checkItem($value, $rule, $rule_data = null)
	{
		if($rule_data !== null){
			$resule = call_user_func_array( array($this, $rule), array($value, $rule_data) );
		}
		else{
			$resule = call_user_func_array( array($this, $rule), array($value) );
		}

		return $resule;
	}

	/**
	 * 获取数组或字符串长度
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	protected function getLength($value)
	{
		if(is_array($value)){
			$length = count($value);
		}
		else{
			$length = mb_strlen((string)$value, 'UTF-8');
		}

		return $length;
	}


	###############################  验证规则  ###################################

	/**
	 * 不能为空
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function required($value)
	{
		return !empty($value) || '0' == $value;
	}

	/**
	 * 数字
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function num($value)
	{
		return is_numeric($value);
	}

	/**
	 * 最大值
	 *
	 * @param  [type] $value [description]
	 * @param  [type] $max   [description]
	 * @return [type]        [description]
	 */
	public function max($value, $max)
	{
		return $this->num($value) && $value <= $max;
	}

	/**
	 * 最小值
	 *
	 * @param  [type] $value [description]
	 * @param  [type] $max   [description]
	 * @return [type]        [description]
	 */
	public function min($value, $min)
	{
		return $this->num($value) && $value >= $min;
	}

	/**
	 * 指定长度(数组或字符串)
	 *
	 * @param  [type] $value  [description]
	 * @param  [type] $length [description]
	 * @return [type]         [description]
	 */
	public function length($value, $length)
	{
		return $this->getLength($value) == $length;
	}

	/**
	 * 最大长度(数组或字符串)
	 *
	 * @param  [type] $value [description]
	 * @param  [type] $max   [description]
	 * @return [type]        [description]
	 */
	public function maxLength($value, $maxLength)
	{
		return $this->getLength($value) <= $maxLength;
	}

	/**
	 * 最小长度(数组或字符串)
	 *
	 * @param  [type] $value     [description]
	 * @param  [type] $minLength [description]
	 * @return [type]            [description]
	 */
	public function minLength($value, $maxLength)
	{
		return $this->getLength($value) >= $maxLength;
	}

	/**
	 * 最后日期
	 *
	 * @param  [type] $value [description]
	 * @param  [type] $date  [description]
	 * @return [type]        [description]
	 */
	public function after($value, $date)
	{
		return strtotime($value) >= strtotime($date);
	}

	/**
	 * 最早日期
	 *
	 * @param  [type] $value [description]
	 * @param  [type] $date  [description]
	 * @return [type]        [description]
	 */
	public function before($value, $date)
	{
		return strtotime($value) <= strtotime($date);
	}

	/**
	 * 正则验证
	 *
	 * @param  [type] $value  [description]
	 * @param  [type] $regexp [description]
	 * @return [type]         [description]
	 */
	public function regexp($value, $regexp)
	{
		// 判断是否存在'/'，不存在则补上
		if(strpos($regexp, '/') !== 0){
            // 不是正则表达式则两端补上/
            $regexp = '/^' . $regexp . '$/';
        }

        return preg_match($regexp, $value) === 1;
	}

	/**
	 * IP地址
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function ip($value)
	{
		return preg_match($this->regex['ip'], $value) === 1;
	}

	/**
	 * 手机号码
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function moble($value)
	{
		return preg_match($this->regex['moble'], $value) === 1;
	}

	/**
	 * 邮箱地址
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function email($value)
	{
		return preg_match($this->regex['email'], $value) === 1;
	}

	/**
	 * 中文，只支持UTF-8格式编码
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function china($value)
	{
		return preg_match($this->regex['china'], $value) === 1;
	}

	/**
	 * 字母和数字
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function language($value)
	{
		return preg_match($this->regex['language'], $value) === 1;
	}

	/**
	 * 字母
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function alpha($value)
	{
		return preg_match($this->regex['alpha'], $value) === 1;
	}

	/**
	 * 只允许字母、数字和下划线 破折号
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function account($value)
	{
		return preg_match($this->regex['account'], $value) === 1;
	}

	/**
	 * 大于0的正整数
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function id($value)
	{
		return $this->int($value) && ($value > 0) ;
	}

	/**
	 * 验证是否为一个有效的日期
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function date($value)
	{
		return false !== strtotime($value);
	}

	/**
	 * 有效URL
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function url($value)
	{
        return false !== filter_var($value, FILTER_VALIDATE_URL);
	}

	/**
	 * 浮点数
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function float($value)
	{
		return false !== filter_var($value, FILTER_VALIDATE_FLOAT);
	}

	/**
	 * 整数
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function int($value)
	{
		return (is_numeric($value) && is_int($value + 0));
	}

	/**
	 * 只允许某些值
	 *
	 * @param  [type] $value [description]
	 * @param  [type] $in    [description]
	 * @return [type]        [description]
	 */
	public function in($value, $in)
	{
		return in_array($value, explode(',', $in));
	}

	/**
	 * 不允许某些值
	 *
	 * @param  [type] $value [description]
	 * @param  [type] $notin [description]
	 * @return [type]        [description]
	 */
	public function notIn($value, $notin)
	{
		return !in_array($value, explode(',', $notin));
	}

}