<?php
namespace interactivesolutions\honeycombscripts\commands;

use interactivesolutions\honeycombcore\commands\HCCommand;
use Nette\Reflection\AnnotationsParser;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

class HCDocs extends HCCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hc:docs {path?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates DOC.html file of the given class';

    /**
     * Execute the console command.
     *
     * @return class info
     */
    public function handle ()
    {
        $codeBlockControllers = '';
        $codeBlockSectionControllers = '';
        $classesInfo = null;

        if('path' == null)
            $this->error('Path mus be given');

        if (file_exists($this->argument('path'). 'docs'))
        {
            $this->info ('Deleting existing directory');
            $it = new RecursiveDirectoryIterator($this->argument('path'). 'docs', RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it,
                RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $file) {
                if ($file->isDir()){
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($this->argument('path'). 'docs');
        }

        $this->createDirectory($this->argument('path') . 'docs');         // creates one

        $this->createWebsiteFrame($this->argument('path'));

        $this->info ('Website frame has been created');

        foreach ($this->getPhpFiles($this->argument('path')) as $parsedClass) {

            $className = AnnotationsParser::parsePhp(file_get_contents($parsedClass));

            if (sizeof($className) == 0)
                continue;

            $class = new \Go\ParserReflection\ReflectionClass(array_keys($className)[0]);


            if(strpos($parsedClass->getRelativePath(),'commands') != false)
            {
                $info = ([
                    'classInfo'         => $this->getClassInfo($class),
                    'classType'         => $this->getClassType($class),
                    'classInheritance'  => $this->getClassInheritance($class),
                    'classProperties'   => $this->getClassProperties($class),
                    'commandsInfo'      => $this->getCommands($class)
                ]);
                $classesInfo['commands'][] = $info;

            }
            elseif((strpos($parsedClass->getRelativePath(),'controllers') != false) && (strpos($parsedClass->getRelativePath(),'controllers/traits') != true))
            {
                $info = ([
                    'classInfo'         => $this->getClassInfo($class),
                    'classType'         => $this->getClassType($class),
                    'classInheritance'  => $this->getClassInheritance($class),
                    'classProperties'   => $this->getClassProperties($class),
                    'classMethods'      => $this->getClassMethods($class)
                ]);
                $classesInfo['controllers'][] = $info;
//                $codeBlockControllers .= $this->createControllerBlockCode($info, $this->argument('path'))['code'];
//                $codeBlockSectionControllers .= '<li><a class="scrollto" href="#'. $this->createControllerBlockCode($info, $this->argument('path'))['className'] .'">'.$this->createControllerBlockCode($info, $this->argument('path'))['className'].'</a></li>';

            }
            elseif(strpos($parsedClass->getRelativePath(),'middleware') != false)
            {
                $info = ([
                    'classInfo'         => $this->getClassInfo($class),
                    'classType'         => $this->getClassType($class),
                    'classInheritance'  => $this->getClassInheritance($class),
                    'classProperties'   => $this->getClassProperties($class),
                    'classMethods'      => $this->getClassMethods($class)
                ]);
                $classesInfo['middleware'][] = $info;

            }
        }

        if($classesInfo == null)
        {
            $this->error('There are no controllers or commands!');
            exit();
        }
        $this->createDocFile($classesInfo, $codeBlockControllers, $codeBlockSectionControllers);
    }

















    public function createControllerMenu($classesInfo)
    {
        //dd($classesInfo['controllers']);
        $file = $this->file->get (__DIR__ . '/templates/docs/controllersMenu.template.txt');
        $output = '';

        foreach ($classesInfo['controllers'] as $value) {
            $field = str_replace ('{className}', $value['classInfo']['className'], $file);
            $output .= $field;
        }

        return $output;
    }
    public function createControllerRow($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/controllersRow.template.txt');
        $output = '';
        if(isset($classesInfo['controllers'])) {
            foreach ($classesInfo['controllers'] as $value) {
                $field = str_replace('{packageName}', $value['classInfo']['name'], $file);
                $field = str_replace('{className}', $value['classInfo']['className'], $field);
                $field = str_replace('{classInheritance}', implode(' &#8594 ', $value['classInheritance']), $field);
                $field = str_replace('{methods}', $this->controllerPublicMethods($value), $field);
                $field = str_replace('{privateMethods}', $this->controllerPrivateMethods($value), $field);
                $field = str_replace('{protectedMethods}', $this->controllerProtectedMethods($value), $field);

                $output .= $field;
            }

        }

        //dd($output);
        return $output;
    }
    public function controllerPublicMethods($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/controllersMethod.template.txt');
        $output = '';

        foreach($classesInfo['classMethods']['publicMethods'] as $value)
        {
            if($value != null) {
                $field = str_replace('{methodName}', $value['method'], $file);
                $field = str_replace('{methodDescription}', $value['comment'], $field);
                $field = str_replace('{className}', $classesInfo['classInfo']['className'], $field);
                $output .= $field;
            }
        }

        return $output;
    }
    public function controllerPrivateMethods($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/controllersMethodPrivate.template.txt');
        $output = '';

        foreach($classesInfo['classMethods']['privateMethods'] as $value)
        {
            if($value != null) {
                $field = str_replace('{methodName}', $value['method'], $file);
                $field = str_replace('{methodDescription}', $value['comment'], $field);
                $field = str_replace('{className}', $classesInfo['classInfo']['className'], $field);
                $output .= $field;
            }
        }

        return $output;
    }
    public function controllerProtectedMethods($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/controllersMethodProtected.template.txt');
        $output = '';

        foreach($classesInfo['classMethods']['protectedMethods'] as $value)
        {
            if($value != null) {
                $field = str_replace('{methodName}', $value['method'], $file);
                $field = str_replace('{methodDescription}', $value['comment'], $field);
                $field = str_replace('{className}', $classesInfo['classInfo']['className'], $field);
                $output .= $field;
            }
        }

        return $output;
    }
























    public function createMiddlewareMenu($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/middlewareMenu.template.txt');
        $output = '';

        foreach ($classesInfo['middleware'] as $value) {
            $field = str_replace ('{className}', $value['classInfo']['className'], $file);
            $output .= $field;
        }

        return $output;
    }
    public function createMiddlewareRow($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/middlewareRow.template.txt');
        $output = '';
        if(isset($classesInfo['middleware'])) {
            foreach ($classesInfo['middleware'] as $value) {
                $field = str_replace('{packageName}', $value['classInfo']['name'], $file);
                $field = str_replace('{className}', $value['classInfo']['className'], $field);
                $field = str_replace('{classInheritance}', implode(' &#8594 ', $value['classInheritance']), $field);
                $field = str_replace('{methods}', $this->middlewarePublicMethods($value), $field);
                $field = str_replace('{protectedMethods}', $this->middlewareProtectedMethods($value), $field);
                $field = str_replace('{privateMethods}', $this->middlewarePrivateMethods($value), $field);


                $output .= $field;
            }

        }
        return $output;
    }
    public function middlewarePublicMethods($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/middlewareMethod.template.txt');
        $output = '';

            foreach($classesInfo['classMethods']['publicMethods'] as $value)
            {
                if($value != null) {
                    $field = str_replace('{methodName}', $value['method'], $file);
                    $field = str_replace('{methodDescription}', $value['comment'], $field);
                    $field = str_replace('{className}', $classesInfo['classInfo']['className'], $field);
                    $output .= $field;
                }
            }

        return $output;
    }
    public function middlewarePrivateMethods($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/middlewareMethodPrivate.template.txt');
        $output = '';

        foreach($classesInfo['classMethods']['privateMethods'] as $value)
        {
            if($value != null) {
                $field = str_replace('{methodName}', $value['method'], $file);
                $field = str_replace('{methodDescription}', $value['comment'], $field);
                $field = str_replace('{className}', $classesInfo['classInfo']['className'], $field);
                $output .= $field;
            }
        }

        return $output;
    }
    public function middlewareProtectedMethods($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/middlewareMethodProtected.template.txt');
        $output = '';

        foreach($classesInfo['classMethods']['protectedMethods'] as $value)
        {
            if($value != null) {
                $field = str_replace('{methodName}', $value['method'], $file);
                $field = str_replace('{methodDescription}', $value['comment'], $field);
                $field = str_replace('{className}', $classesInfo['classInfo']['className'], $field);
                $output .= $field;
            }
        }

        return $output;
    }























    /**
     * @param $classesInfo
     * @return string
     */
    public function createCommandsRow($classesInfo)
    {

        $file = $this->file->get (__DIR__ . '/templates/docs/commandsRow.template.txt');
        $output = '';


        foreach ($classesInfo['commands'] as $value)
        {
            $field = str_replace ('{packageName}', $value['classInfo']['name'], $file);
            $field = str_replace ('{className}', $value['classInfo']['className'], $field);
            $field = str_replace ('{classInheritance}', implode(' &#8594 ', $value['classInheritance']), $field);
            $field = str_replace ('{commandName}', $value['commandsInfo']['signature'], $field);
            $field = str_replace ('{commandDescription}', $value['commandsInfo']['description'], $field);

            $output .= $field;
        }

        return $output;
    }

    public function createCommandsMenu($classesInfo)
    {
        $file = $this->file->get (__DIR__ . '/templates/docs/commandsMenu.template.txt');
        $output = '';

        foreach ($classesInfo['commands'] as $value) {
            $field = str_replace ('{className}', $value['classInfo']['className'], $file);
            $output .= $field;
        }

        return $output;
    }

    /**
     * Create doc file
     *
     * @param $classesInfo
     */
    public function createDocFile($classesInfo, $codeBlockControllers, $codeBlockSectionControllers)
    {
        $composer = $this->file->get ($this->argument('path') . '/composer.json');

        if(isset($classesInfo['middleware'])) {
            $this->createFileFromTemplate([
                "destination" => $this->argument('path') . 'docs/docs.html',
                "templateDestination" => __DIR__ . '/templates/docs/docs.template.txt',
                "content" => [
                    "packageName" => explode(':', explode(',', $composer)[0])[1],
                    "name" => $classesInfo['commands'][0]['classInfo']['name'],
                    "commands" => $this->createCommandsRow($classesInfo),
                    "commandsMenu" => $this->createCommandsMenu($classesInfo),
                    //"controllers" => $codeBlockControllers,
                    //"sectionControllers" => $codeBlockSectionControllers,
                    "middleware" => $this->createMiddlewareRow($classesInfo),
                    "middlewareMenu" => $this->createMiddlewareMenu($classesInfo),
                    "controllers" => $this->createControllerRow($classesInfo),
                    "controllersMenu" => $this->createControllerMenu($classesInfo)
                ],
            ]);
        }
        else
        {
            $this->createFileFromTemplate([
                "destination" => $this->argument('path') . 'docs/docs.html',
                "templateDestination" => __DIR__ . '/templates/docs/docs.template.txt',
                "content" => [
                    "packageName" => explode(':', explode(',', $composer)[0])[1],
                    "name" => $classesInfo['commands'][0]['classInfo']['name'],
                    "commands" => $this->createCommandsRow($classesInfo),
                    "commandsMenu" => $this->createCommandsMenu($classesInfo),
                    "controllers" => $this->createControllerRow($classesInfo),
                    "controllersMenu" => $this->createControllerMenu($classesInfo),
                    "middleware" => '',
                    "middlewareMenu" => ''
                ],
            ]);
        }
    }

    /**
     * Create website frame
     *
     * @param $path
     */
    public function createWebsiteFrame($path){
        $fileList = [
            //assets/css
            [
                "destination" => $path . '/docs/assets/css/styles.css',
                "templateDestination" => __DIR__ . '/templates/docs/assets/css/styles.template.txt',
            ],
            //assets/js
            [
                "destination" => $path . '/docs/assets/js/main.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/js/main.template.txt',
            ],
            //assets/less
            [
                "destination" => $path . '/docs/assets/less/base.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/base.template.txt',
            ],
            [
                "destination" => $path . '/docs/assets/less/doc.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/doc.template.txt',
            ],
            [
                "destination" => $path . '/docs/assets/less/landing.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/landing.template.txt',
            ],
            [
                "destination" => $path . '/docs/assets/less/mixins.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/mixins.template.txt',
            ],
            [
                "destination" => $path . '/docs/assets/less/styles.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/styles.template.txt',
            ],
            [
                "destination" => $path . '/docs/assets/less/theme-default.less',
                "templateDestination" => __DIR__ . '/templates/docs/assets/less/theme-default.template.txt',
            ],
            //assets/plugins
            [
                "destination" => $path . '/docs/assets/plugins/jquery-1.12.3.min.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/jquery-1123-min.template.txt',
            ],
            //assets/plugins/bootstrap/css
            [
                "destination" => $path . '/docs/assets/plugins/bootstrap/css/bootstrap.css',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/bootstrap/css/bootstrap.template.txt',
            ],
            [
                "destination" => $path . '/docs/assets/plugins/bootstrap/css/bootstrap.min.css',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/bootstrap/css/bootstrap-min.template.txt',
            ],
            //assets/plugins/bootstrap/js
            [
                "destination" => $path . '/docs/assets/plugins/bootstrap/js/bootstrap.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/bootstrap/js/bootstrap.template.txt',
            ],
            [
                "destination" => $path . '/docs/assets/plugins/bootstrap/js/bootstrap.min.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/bootstrap/js/bootstrap-min.template.txt',
            ],
            [
                "destination" => $path . '/docs/assets/plugins/bootstrap/js/npm.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/bootstrap/js/npm.template.txt',
            ],
            //assets/plugins/prism
            [
                "destination" => $path . '/docs/assets/plugins/prism/prism.css',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/prism/prism-css.template.txt',
            ],
            [
                "destination" => $path . '/docs/assets/plugins/prism/prism.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/prism/prism-js.template.txt',
            ],
            //assets/plugins/prism/min
            [
                "destination" => $path . '/docs/assets/plugins/prism/min/prism-min.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/prism/min/prism-min.template.txt',
            ],
            //assets/plugins/jquery-scrollTo
            [
                "destination" => $path . '/docs/assets/plugins/jquery-scrollTo/jquery.scrollTo.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/jquery-scrollTo/jquery.scrollTo.template.txt',
            ],
            [
                "destination" => $path . '/docs/assets/plugins/jquery-scrollTo/jquery.scrollTo.min.js',
                "templateDestination" => __DIR__ . '/templates/docs/assets/plugins/jquery-scrollTo/jquery.scrollTo.min.template.txt',
            ],


        ];

        foreach ($fileList as $value)
            $this->createFileFromTemplate($value);

    }









  /*
    public function createControllerBlockCode($classData){

        $inheritance = ' ';
        $protectedMethods = ' ';
        $privateMethods = ' ';
        $publicMethods = ' ';
        $publicMethods = $this->createControllerPublicMethodsBlockCode($classData);
        $protectedMethods = $this->createControllerProtectedMethodsBlockCode($classData);
        $privateMethods = $this->createControllerPrivateMethodsBlockCode($classData);
        foreach ($classData['classInheritance'] as $inherit){

            $inheritance .= ' &#x2192 ' . $inherit;
        }
        $topCodeBlock = '<p><span style="font-size: 16px;"><strong>Package: </strong></span>'. $classData['classInfo']['name'] .'</p>
                  <p><span style="font-size: 16px;"><strong>Class:</strong></span> class '. $classData['classInfo']['className'] .'</p>
                  <p><span style="font-size: 16px;"><strong>Inheritance:</strong></span> '. $classData['classInfo']['className'] .' Inheritance ' . $inheritance .'</p>';

        $codeBlock ='<div id="' . $classData['classInfo']['className'] . '" class="section-block">'.
            $topCodeBlock . '
                        <div class="code-block">
                            <h6>' . $classData['classInfo']['className'] . '</h6>
                            <div class="table-responsive">
                                <table class="table" style="width:80%">
                                    <thead>
                                        <tr>
                                            <th>Methods</th>
                                            <th style="text-align: right;">Defined By</th>
                                        </tr>
                                    </thead>
                                   ' . $publicMethods . $protectedMethods . $privateMethods . '
                                </table>
                            </div>
                        </div>
                    </div>';

        return $info = [
            'code' => $codeBlock,
            'className' => $classData['classInfo']['className']
        ];
    }
    public function createControllerPublicMethodsBlockCode($classData){
        $tablecontent = ' ';

            foreach ($classData['classMethods']['publicMethods'] as $m)
            {
                $row = "<tr>
                            <td><strong>public function " . $m['method'] . "()" . "</strong></br>" . $m['comment']. "</td>
						    <td style='text-align: right;'>" . $classData['classInfo']['className'] . "</td>
					    </tr>";
                $tablecontent .= $row;
            }
        $tablecontent =  '<tbody>' . $tablecontent . '</tbody>';
        return $tablecontent;
    }
    public function createControllerProtectedMethodsBlockCode($classData){
        $tablecontent = '';
            foreach ($classData['classMethods']['protectedMethods'] as $m)
            {
                $row = "<tr>
                            <td><strong>protected function " . $m['method'] . "()" . "</strong></br>" . $m['comment']. "</td>
						    <td style='text-align: right;'>" . $classData['classInfo']['className'] . "</td>
					    </tr>";
                $tablecontent .= $row;
            }
        $tablecontent =  '<tbody>' . $tablecontent . '</tbody>';
        return $tablecontent;
    }
    public function createControllerPrivateMethodsBlockCode($classData){
        $tablecontent = '';
            foreach ($classData['classMethods']['privateMethods'] as $m)
            {
                $row = "<tr>
                            <td><strong>private function " . $m['method'] . "()" . "</strong></br>" . $m['comment']. "</td>
						    <td style='text-align: right;'>" . $classData['classInfo']['className'] . "</td>
					    </tr>";
                $tablecontent .= $row;
            }
        $tablecontent =  '<tbody>' . $tablecontent . '</tbody>';
        return $tablecontent;
    }
*/












    /**
     * You will need to install 'symfony/finder' package
     * URL: http://symfony.com/doc/current/components/finder.html
     *
     * Gets all php files in provided directory
     *
     * @param $directory
     * @return array
     */
    public function getPhpFiles($directory)
    {
        $finder = new Finder();
        $finder->files()->in(base_path() .'/' . $directory);

        foreach ($finder as $file)
        {
            if($file->getExtension() == 'php')
                $files[] = $file;
        }

        return $files;
    }

    public function getCommands($parsedClass)
    {
        $commandInfo = [
            'signature'     => $parsedClass->getDefaultProperties()['signature'],
            'description'   => $parsedClass->getDefaultProperties()['description']
        ];

        return $commandInfo;
    }
    /**
     * Get class basic info
     *
     * @param $parsedClass
     * @return array
     */
    public function getClassInfo($parsedClass)
    {
        if($parsedClass->name != null) {
            // Class info
            $name = explode("\\", $parsedClass->name)[1];                                                               // returns package name
            $gitHub = '';                                                                                               // returns git hub url
            $installName = explode("\\", $parsedClass->getName())[0] . '/' . explode("\\", $parsedClass->getName())[1];  // returns install name
            $serviceProvider = '';                                                                                      // returns service provider
            $className = $parsedClass->getShortName();                                                                  // returns class name

            return $info = ([
                'name' => $name,
                'gitHub' => $gitHub,
                'installName' => $installName,
                'serviceProvider' => $serviceProvider,
                'className' => $className
            ]);
        }
    }

    /**
     * Gets class type
     *
     * @param $parsedClass
     * @return null|string
     */
    public function getClassType($parsedClass)
    {
        $classType = null;

        if($parsedClass->isAbstract())                                                                              // checks if class is abstract
            $classType = 'abstract';
        elseif($parsedClass->isFinal())                                                                             // checks if class is final
            $classType = 'final';

        return $classType;
    }

    /**
     * Gets class inheritance
     *
     * @param $parsedClass
     * @return array
     */
    public function getClassInheritance($parsedClass)
    {
        $className = $parsedClass->getShortName();                                                                  // returns class name

        $inheritance[] = $className;

        $parentClass = $parsedClass->getParentClass();
        while($parentClass)                                                                                         // while parent class is true
        {
            $inheritance[] = $parentClass->getShortName();                                                          // add parent class name to the inheritance array
            $parentClass = $parentClass->getParentClass();                                                          // get next parent class
        }

        return $inheritance;
    }

    /**
     * Gets class properties
     *
     * @param $parsedClass
     * @return array
     */
    public function getClassProperties($parsedClass)
    {
        $properties = null;

        foreach ($parsedClass->getProperties() as $property)
        {
            if($parsedClass->getName() == $property->class) {                                                       // check only for the required class
                if ($property->isPrivate())                                                                         // check whether class is private
                    $type = 'private';
                elseif ($property->isPublic())                                                                      // check whether class is public
                    $type = 'public';
                elseif ($property->isProtected())                                                                   // check whether class is protected
                    $type = 'protected';

                $property = [
                    'className' => $property->getDeclaringClass()->getName(),
                    'propertyName' => $property->getName(),
                    'type' => $type,
                    'declaredBy' => $property->getDeclaringClass()->getShortName(),
                    'comment' => str_replace(['     ', '/**', '*/', '* ', '*', "/\r|\n/"], '', $property->getDocComment())
                ];
                $properties[] = $property;
            }
        }

        return $properties;
    }

    /**
     * Gets class methods
     *
     * @param $parsedClass
     * @return array
     */
    public function getClassMethods($parsedClass)
    {
        foreach ($parsedClass->getMethods() as $method) {
            $method1[] = $this->getPublicMethods($method, $parsedClass);
            $method2[] = $this->getProtectedMethods($method,$parsedClass);
            $method3[] = $this->getPrivateMethods($method,$parsedClass);
        }

        $methods = ([
            'publicMethods' => array_filter($method1),
            'protectedMethods' => array_filter($method2),
            'privateMethods' => array_filter($method3)
        ]);


        return $methods;
    }

    /**
     * get public methods
     *
     * @param $method
     * @param $parsedClass
     * @return array
     */
    function getPublicMethods($method,$parsedClass){

        $filterResult = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
            $string = trim(preg_replace('/\s\s+/', ' ',
                $method->getDocComment()))), 0 , 2);

        if($parsedClass->getName() == $method->class && $method->isPublic())
        {
            if($method->getDocComment() == null){
                $comment = null;
                $params = null;
                $return = null;

            }elseif($filterResult == "@r"){
                $comment = null;
                $params = null;
                $return = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
                    $string = trim(preg_replace('/\s\s+/', ' ',
                        $method->getDocComment()))), 8);
            }
            else{
                $method_data = explode("*", $string = trim(preg_replace('/\s\s+/', ' ', str_replace(['     ', '/**', '* ', "/\r|\n/"], '', $method))), 2);
                $comment = $method_data[0];

                $rreturn = explode("*/", explode("@return", $method_data[1], 2)[1], 2);
                //echo $parsedClass->getName() . '     ';
                $return = $rreturn[0];

                $params = array_filter(explode(',', str_replace(' @param ', ',', explode(" @return ", $method_data[1], 2)[0])));
                $return = str_replace(' ', '', $return);
            }

            $post_data = array(
                'method' => $method->name,
                'param' => $params,
                'return' => $return,
                'comment' => $comment
            );
            return $post_data;
        }

    }

    /**
     * get protected methods
     *
     * @param $method
     * @param $parsedClass
     * @return array
     */
    function getProtectedMethods($method,$parsedClass){

        $filterResult = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
            $string = trim(preg_replace('/\s\s+/', ' ',
                $method->getDocComment()))), 0 , 2);

        if($parsedClass->getName() == $method->class && $method->isProtected())
        {
            if($method->getDocComment() == null){
                $comment = null;
                $params = null;
                $return = null;

            }elseif($filterResult == "@r"){
                $comment = null;
                $params = null;
                $return = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
                    $string = trim(preg_replace('/\s\s+/', ' ',
                        $method->getDocComment()))), 8);
            }else{
                $method_data = explode("*", $string = trim(preg_replace('/\s\s+/', ' ', str_replace(['     ', '/**', '* ', "/\r|\n/"], '', $method))), 2);

                $comment = $method_data[0];


                $rreturn = explode("*/", explode("@return", $method_data[1], 2)[1], 2);
                $return = $rreturn[0];

                $params = array_filter(explode(',', str_replace(' @param ', ',', explode(" @return ", $method_data[1], 2)[0])));
                $return = str_replace(' ', '', $return);

            }
            $post_data = array(
                'method' => $method->name,
                'param' => $params,
                'return' => $return,
                'comment' => $comment
            );

            return $post_data;
        }

    }

    /**
     * get private methods
     *
     * @param $method
     * @param $parsedClass
     * @return array
     */
    function getPrivateMethods($method,$parsedClass){

        $filterResult = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
            $string = trim(preg_replace('/\s\s+/', ' ',
                $method->getDocComment()))), 0 , 2);

        if($parsedClass->getName() == $method->class && $method->isPrivate())
        {
            if($method->getDocComment() == null){
                $comment = null;
                $params = null;
                $return = null;

            }elseif($filterResult == "@r"){
                $comment = null;
                $params = null;
                $return = substr(str_replace(['     ', '/** ', '* ', " */" ,"/\r|\n/"], '',
                    $string = trim(preg_replace('/\s\s+/', ' ',
                        $method->getDocComment()))), 8);
            }else{
                $method_data = explode("*", $string = trim(preg_replace('/\s\s+/', ' ', str_replace(['     ', '/**', '* ', "/\r|\n/"], '', $method))), 2);
                $comment = $method_data[0];


                $rreturn = explode("*/", explode("@return", $method_data[1], 2)[1], 2);
                $return = $rreturn[0];

                $params = array_filter(explode(',', str_replace(' @param ', ',', explode(" @return ", $method_data[1], 2)[0])));
                $return = str_replace(' ', '', $return);

            }
            $post_data = array(
                'method' => $method->name,
                'param' => $params,
                'return' => $return,
                'comment' => $comment
            );

            return $post_data;
        }

    }

}