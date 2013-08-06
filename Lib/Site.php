<?php
namespace Lib;
class Site {
    private $router = array();
    private $controller;
    private $controllerPath;
    private $method;
    private $folders = array();
    private $url;

    public function __construct() {
        $this->url = Url::instance();
    }
    
    /**
     * 开始执行contrller对应的method
     */
    public function run() {
        $this->setContrllerMethod()->boot();
    }

    /**
     * controller的文件夹层级
     * @return array
     */
    public function getFolder() {
        return $this->folders;
    }

    /**
     * 获取controller的类名
     * @return string
     */
    public function getController() {
        return $this->controller;
    }

    /**
     * 获取方法名
     * @return type
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * 设置自定义路由规则
     * @param array $router 键为url规则,值为contrller和method构成的键值对数组
     * @return \Lib\Site
     */
    public function setRouter(array $router) {
        $this->router = $router;
        return $this;
    }

    /**
     * 设置controller类名和方法名
     * @return \Lib\Site
     */
    private function setContrllerMethod() {
        $uri = $this->url->uri();
        $segments = $this->url->segments();
        if (!empty($segments)) {
            foreach ($segments as $key => $segment) {
                $dir = '';
                if (!empty($this->folders)) {
                    $dir = implode('/', $this->folders) . '/';
                }
                $classFileName = $this->controllerFileName($segment);
                $classFilePath = $this->controllerFilePath($dir . $classFileName);
                $classDirPath = $this->controllerFilePath($dir . $segment);
                if (is_file($classFilePath)) {
                    $this->controller = $this->controllerClassName($segment);
                    $this->method = isset($segments[$key + 1]) ? $segments[$key + 1] : 'index';
                    $this->controllerPath = $classFilePath;
                } elseif (is_dir($classDirPath)) {
                    $this->folders[] = $segment;
                }
            }
            if (is_null($this->controller) && !empty($this->folders)) {
                $lastPath = end($this->folders);
                $lastSegment = end($segments);
                $this->controller = $this->controllerClassName($lastPath);
                $this->controllerPath = $this->controllerFilePath(implode('/', $this->folders) . '/' . $this->controllerFileName($lastPath));
                $this->method = $lastSegment == $lastPath ? 'index' : $lastSegment;
            } elseif (is_null($this->controller) && !empty($this->router)) {
                foreach ($this->router as $pattern => $controller) {
                    if (preg_match($pattern, $uri, $matches)) {
                        $controllerInfo = explode('/', current(array_keys($controller)));
                        $controllerName = array_pop($controllerInfo);
                        $this->folders = $controllerInfo;
                        $dirInfo = empty($this->folders) ? '' : implode('/', $this->folders) . '/';
                        $this->controllerPath = $this->controllerFilePath($dirInfo . $this->controllerFileName($controllerName));
                        $this->controller = $this->controllerClassName($controllerName);
                        $this->method = current($controller);
                        foreach ($matches as $matchKey => $matchValue) {
                            if (!ctype_digit((string)$matchKey)) {
                                $this->url->setGet($matchKey, $matchValue);
                            }
                        }
                    }
                }
            }
            if (is_null($this->controller)) {
                $this->controller = 'Home_Controller';
                $this->method = current($segments);
                $this->controllerPath = $this->controllerFilePath($this->controllerFileName('home'));
            }
        } else {
            $this->controller = 'Home_Controller';
            $this->method = 'index';
            $this->controllerPath = $this->controllerFilePath($this->controllerFileName('home'));
        }
        if (($ajaxValue = $this->url->ajax())) {
            $this->method = $this->ajaxMethod($ajaxValue);
        }
        return $this;
    }

    /**
     * 根据controller的类名和方法尝试调用
     */
    private function boot() {
        if ($this->controller && $this->method && is_file($this->controllerPath)) {
            require_once $this->controllerPath;
            $className = "\Controller\\{$this->controller}";
            $reflection = new \ReflectionClass($className);
            if ($reflection->isAbstract()) {
                $this->pageNotFound();
            }
            $controller = new $className();
            if (method_exists($controller, $this->method) && is_callable(array($controller, $this->method))) {
                $controller->init($this)->{$this->method}();
            } else {
                $controller->pageNotFound();
            }
        } else {
            $this->pageNotFound();
        }
    }

    /**
     * 404页面
     */
    private function pageNotFound() {
        header("HTTP/1.0 404 Not Found");
        exit('page not found!!!');
    }

    /**
     * 根据url段生成controller的类名
     * @param string $segment url段
     * @return string
     */
    private function controllerClassName($segment) {
        return ucfirst($segment) . '_Controller';
    }

    /**
     * 根据url段生成controller的文件名
     * @param string $segment
     * @return string
     */
    private function controllerFileName($segment) {
        return $this->controllerClassName($segment) . '.php';
    }

    /**
     * 生成controller的路径
     * @param string $fileName 文件名
     * @return string
     */
    private function controllerFilePath($fileName) {
        return CONTROLLER_PATH . $fileName;
    }

    /**
     * 生成controller的ajax方法名
     * @param string $segment _ajax_参数的value
     * @return string
     */
    private function ajaxMethod($segment) {
        return 'ajax_' . ($this->method && $this->method != 'index' ? $this->method . '_' : '') . $segment;
    }

}

?>
