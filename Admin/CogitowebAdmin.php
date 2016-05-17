<?php

namespace Sonata\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\CoreBundle\Model\Metadata;
use Sonata\AdminBundle\Route\RouteCollection;

use Doctrine\Common\Util\Inflector as Inflector;

/**
 * Description of CogitowebAdmin
 *
 * @author Daniele Artico <daniele.artico@cogitoweb.it>
 */
class CogitowebAdmin extends Admin
{
	const PHP_ARRAY_FORMAT = "php";
	const POSTGRES_ARRAY_FORMAT = "postgres";

	public function getInstance() {
		return $this;
	}

	/**
	 * The underlaying object is new? 
	 *
	 * @return bool
	 */
	public function isNew() 
	{
		return ($this->getRoot()->getSubject() && $this->getRoot()->getSubject()->getId()) ? false : true;
	}

	/**
	 * 
	 * @return string
	 */
	public function getLocale() 
	{
		$locale = ($this->request) ? $this->request->get('_locale') : null;

		if($locale)
		{
//			$fake = new \Cogitoweb\TemplateBundle\Entity\BaseTranslatedEntity();
//			$locale = $fake->getMyDefaultLocale();
		}

		return $locale;
	}

	/**
	 * E' una chiamata API?
	 * 
	 * @return boolean
	 */
	public function isAPI() {
		if ($this->request && strpos($this->request->getRequestUri(), '/api/admin/') !== false) {
			return true;
		}
		return false;
	}

	/**
	 * Returns true if the per page value is allowed, false otherwise
	 *
	 * @param int $perPage
	 *
	 * @return bool
	 */
	public function determinedPerPageValue($perPage) {
		// 2z -> rimosso controllo di intervallo di paginazione

		return true; //in_array($perPage, $this->perPageOptions);
	}

	protected $formOptions = array(
		'validation_groups' => array('CRUD'),
		'cascade_validation' => true,
		'error_bubbling' => true,
	);

	public function getBatchActions() {
		return array();
	}

	/**
	 *
	 * @var type 
	 */
	public $tabPersistentFields = null;

	/**
	 *
	 * @var type 
	 */
	public $adminContextTemplate = null;

	/**
	 *
	 * @var type 
	 */
	public $adminRouteTemplate = null;
	protected $defaultDateFormat = null;

	/**
	 * 
	 * @return getDateFormat
	 */
	public function getDefaultDateFormat($short = true, $filter = true) {
		return $this->getDateTimeService()->getDateFormat($short, $filter);
	}

	protected $defaultDateTimeFormat = null;

	/**
	 * 
	 * @return getDateTimeFormat
	 */
	public function getDefaultDateTimeFormat($short = true, $filter = true) {
		return $this->getDateTimeService()->getDateTimeFormat($short, $filter);
	}

	protected $dateTimeService = null;
	protected $optimizeQueryService = null;
	protected $currentUser = null;

	/**
	 * @param string $code
	 * @param string $class
	 * @param string $baseControllerName
	 */
	public function __construct($code, $class, $baseControllerName) {
		if ($baseControllerName === 'SonataAdminBundle:CRUD') {
			$baseControllerName = 'SonataAdminBundle:CogitowebCRUD';
		}

		parent::__construct($code, $class, $baseControllerName);
	}


	/**
	 * @param string $code
	 * @return stdClass
	 */
	public function getService($serviceName) {

		return $this->getConfigurationPool()->getContainer()->get($serviceName);
	}

	/**
	 * 
	 * @return \Cogitoweb\TemplateBundle\Services\DateTimeService $dateTimeService
	 */
	public function getDateTimeService() {

		if ($this->dateTimeService === null) {

			$this->dateTimeService = $this->getConfigurationPool()->getContainer()->get('cogitoweb_template.datetime');

			$this->dateTimeService->setRequest($this->getRequest());
		}

		return $this->dateTimeService;
	}

	/**
	 * 
	 * @return object
	 */
	public function getOptimizeQueryService($service_name) {

		if ($this->optimizeQueryService === null) {

			$this->optimizeQueryService = $this->getConfigurationPool()->getContainer()->get($service_name);
		}

		return $this->optimizeQueryService;
	}

	/**
	 * 
	 * @return Application\Sonata\UserBundle\Entity\User $currentUser
	 */
	public function getCurrenUser() {

		if ($this->currentUser === null) {
			$this->currentUser = $this->getConfigurationPool()->getContainer()->get('security.context')->getToken()->getUser();
		}

		return $this->currentUser;
	}

	/**
	 * aggiunge info di show
	 * 
	 * @param \Sonata\AdminBundle\Show\ShowMapper $showMapper
	 */
	public function addOtherShowInfo(ShowMapper $showMapper) {

		$showMapper->with('label.other_info', array('collapsed' => true))
				->add('id')
				->add('createdAt')
				->add('createdBy')
				->add('updatedAt')
				->add('updatedBy')
		;
	}


	/**
	 * {@inheritdoc}
	 */
	public function removeShowGroup($key)
	{
		$showg = $this->getShowGroups();

		if($showg && is_array($showg) && isset($showg[$key])) {
			unset($showg[$key]);

			$this->setShowGroups($showg);
		}


	}



	/**
	 * Generates the breadcrumbs NO EDIT
	 *
	 * Note: the method will be called by the top admin instance (parent => child)
	 *
	 * @param string                       $action
	 * @param \Knp\Menu\ItemInterface|null $menu
	 *
	 * @return array
	 */
	public function buildBreadcrumbs($action, MenuItemInterface $menu = null) {
		// CGT FB
		if(isset($this->classLabel)){
		  $label = $this->classLabel;
		}
		else{
		  $label=$this->getClassnameLabel();
		}

		if (isset($this->breadcrumbs[$action])) {
			return $this->breadcrumbs[$action];
		}

		if (!$menu) {
			$menu = $this->menuFactory->createItem('root');

			$menu = $menu->addChild(
					$this->trans($this->getLabelTranslatorStrategy()->getLabel('dashboard', 'breadcrumb', 'link'), array(), 'SonataAdminBundle'), array('uri' => $this->routeGenerator->generate('sonata_admin_dashboard'))
			);
		}

		$menu = $menu->addChild(
				//$this->trans($this->getLabelTranslatorStrategy()->getLabel(sprintf('%s_list', $this->getClassnameLabel()), 'breadcrumb', 'link')), array('uri' => $this->hasRoute('list') && $this->isGranted('LIST') ? $this->generateUrl('list') : null)
				$this->trans($this->getLabelTranslatorStrategy()->getLabel(sprintf('%s_list', $label), 'breadcrumb', 'link')), array('uri' => $this->hasRoute('list') && $this->isGranted('LIST') ? $this->generateUrl('list') : null)
		);

		$childAdmin = $this->getCurrentChildAdmin();

		if ($childAdmin) {

			// 2z -> la request non è sempre settata
			if (!$this->request) {
				$this->request = $childAdmin->getRequestFromParents();
			}

			$id = $this->request->get($this->getIdParameter());

			$menu = $menu->addChild(
					$this->toString($this->getSubject()), array('uri' => $this->hasRoute('show') && $this->isGranted('VIEW') ? $this->generateUrl('show', array($this->getIdParameter() => $id)) : null)
			);

			// 2z -> added
			foreach ($childAdmin->getParentsForBreadCrumb() as $c) {
				// CGT FB
				if(isset($c->classLabel)){
				  $c_label = $c->classLabel;
				}
				else{
				  $c_label = $c->getClassnameLabel();
				}
				$menu = $menu->addChild(
						//$c->trans($c->getLabelTranslatorStrategy()->getLabel(sprintf('%s_list', $c->getClassnameLabel()), 'breadcrumb', 'link')), array('uri' => $c->hasRoute('list') && $c->isGranted('LIST') ? $c->generateUrl('list') : null)
						$c->trans($c->getLabelTranslatorStrategy()->getLabel(sprintf('%s_list', $c_label), 'breadcrumb', 'link')), array('uri' => $c->hasRoute('list') && $c->isGranted('LIST') ? $c->generateUrl('list') : null)
				);

				$id = $this->request->get($c->getIdParameter());
				$menu = $menu->addChild(
						$c->toString($c->getSubject()), array('uri' => $c->hasRoute('show') && $c->isGranted('VIEW') ? $c->generateUrl('show', array($c->getIdParameter() => $id)) : null)
				);
			}

			return $childAdmin->buildBreadcrumbs($action, $menu);
		} elseif ($this->isChild()) {

			if ($action == 'list') {
				$menu->setUri(false);
			} elseif ($action != 'create' && $this->hasSubject()) {
				$menu = $menu->addChild($this->toString($this->getSubject()));
			} else {
				$menu = $menu->addChild(
						// CGT FB
						//$this->trans($this->getLabelTranslatorStrategy()->getLabel(sprintf('%s_%s', $this->getClassnameLabel(), $action), 'breadcrumb', 'link'))
						$this->trans($this->getLabelTranslatorStrategy()->getLabel(sprintf('%s_%s', $label, $action), 'breadcrumb', 'link'))
				);
			}
		} elseif ($action != 'list' && $this->hasSubject()) {

			$menu = $menu->addChild($this->toString($this->getSubject()));
		} elseif ($action != 'list') {

			$menu = $menu->addChild(
					// CGT FB
					//$this->trans($this->getLabelTranslatorStrategy()->getLabel(sprintf('%s_%s', $this->getClassnameLabel(), $action), 'breadcrumb', 'link'))
					$this->trans($this->getLabelTranslatorStrategy()->getLabel(sprintf('%s_%s', $label, $action), 'breadcrumb', 'link'))
			);
		}

		return $this->breadcrumbs[$action] = $menu;
	}

	/**
	 * Sovrascritto per permettere comportamento opzionale su filtri
	 * child degli admin
	 * 
	 * {@inheritdoc}
	 */
	public function buildDatagrid() {
		if ($this->datagrid) {
			return;
		}

		$filterParameters = $this->getFilterParameters();

		// transform _sort_by from a string to a FieldDescriptionInterface for the datagrid.
		if (isset($filterParameters['_sort_by']) && is_string($filterParameters['_sort_by'])) {
			if ($this->hasListFieldDescription($filterParameters['_sort_by'])) {
				$filterParameters['_sort_by'] = $this->getListFieldDescription($filterParameters['_sort_by']);
			} else {
				$filterParameters['_sort_by'] = $this->getModelManager()->getNewFieldDescriptionInstance(
						$this->getClass(), $filterParameters['_sort_by'], array()
				);

				$this->getListBuilder()->buildField(null, $filterParameters['_sort_by'], $this);
			}
		}

		// initialize the datagrid
		$this->datagrid = $this->getDatagridBuilder()->getBaseDatagrid($this, $filterParameters);

		$this->datagrid->getPager()->setMaxPageLinks($this->maxPageLinks);

		$mapper = new DatagridMapper($this->getDatagridBuilder(), $this->datagrid, $this);

		// build the datagrid filter
		$this->configureDatagridFilters($mapper);

		// 2z ->
		// aggiunto metodo da sovrascrivere all'occorrenza
		$this->configureChildFilter($mapper);

		// 2z ->
		// aggiunto metodo per additional data
		$this->addExtraQueryResults();

		foreach ($this->getExtensions() as $extension) {
			$extension->configureDatagridFilters($mapper);
		}
	}

	/**
	 * Privata con controllo se la datagrid implementa
	 * 
	 */
	private function addExtraQueryResults() {

		if(method_exists($this->datagrid, 'setAdditionalData')) {

			$this->datagrid->setAdditionalData($this->addQueryResults());

		}
	}

	/**
	 * Vuoto per implementazioni
	 * 
	 */
	public function addQueryResults() {

		return null;
	}

	/**
	 * espone il metodo per sovrascrivere filtri di child
	 * 
	 * @param mixed $mapper
	 */
	public function configureChildFilter(&$mapper) {

		// ok, try to limit to add parent filter
		if ($this->isChild() && $this->getParentAssociationMapping() && !$mapper->has($this->getParentAssociationMapping())) {
			$mapper->add($this->getParentAssociationMapping(), null, array(
				'field_type' => 'sonata_type_model_reference',
				'field_options' => array(
					'model_manager' => $this->getModelManager()
				),
				'operator_type' => 'hidden'
			));
		}
	}

	/**
	 * resituisce un eventuale object parent
	 * 
	 * @return mixed
	 * @throws \Exception
	 */
	public function getParentObject() {

		if (!$this->isChild()) {
			throw new \Exception('non sono un figlio');
		}

		$id_param = $this->getParent()->getIdParameter();
		if($this->request->isXmlHttpRequest())
		{
			$id_param = 'objectId';

			$this->request->attributes->set($this->getParent()->getIdParameter(), 
					$this->request->get($id_param));
		}

		return $this->getParent()->getObject($this->request->get($id_param));
	}

	/**
	 * restituisce un array di id dalla query fornita
	 * 
	 * @param string $sql
	 * @param array $params (chiave valore nome campo, v)
	 * @param type $queryBuilder
	 * @return array
	 */
	public function childGetListRelatedIds($sql, $params, $queryBuilder) {

		$em = $queryBuilder->getEntityManager();
		$connetcion = $em->getConnection();
		$sql = $connetcion->prepare($sql);

		foreach ($params as $k => $v) {
			$sql->bindValue($k, $v);
		}
		$sql->execute();
		$ids = $sql->fetchAll();

		if (!count($ids)) {
			$ids = array(0);
		} else {
			$ids = array_map(function($v) {
				return intval($v['id']);
			}, $ids);
		}

		return $ids;
	}

	/**
	 * Compatibilità con autocomplete
	 * 
	 * @param type $object
	 * @return \Sonata\CoreBundle\Model\Metadata
	 */
	public function getObjectMetadata($object) {
		return new Metadata($this->toString($object));
	}

	//
	///// metodi per multilevel
	//

	protected $explicit_parents = array();
	protected $explicit_parents_all = array();

	/**
	 * Metodo da chiamare da services.yml fornendo un elenco di admin (array) in senso inverso 
	 * rispetto alla gerarchia: padre, nonno, bisnonno
	 * 
	 * @param array $parents
	 */
	public function addParents($parents) {
		array_unshift($parents, $this);
		$this->explicit_parents_all[] = $this;

		for ($i = 1; $i < count($parents); $i++) {
			$parents[$i]->addChild($parents[$i - 1]);
			$parents[$i]->setCurrentChild($parents[$i - 1]);
			$this->explicit_parents[] = $parents[$i];
			$this->explicit_parents_all[] = $parents[$i];
		}
	}

	/**
	 * Ricava la request dalla gerarchia dei padri
	 * usato per i breadcrumbs
	 * 
	 * @return null
	 */
	public function getRequestFromParents() {
		foreach ($this->explicit_parents_all as $p) {
			if ($p->request) {
				return $p->request;
			}
		}

		return null;
	}

	/**
	 * Calcola la profondità dell'albero
	 * 
	 * @param int $tree_depth
	 * @return int
	 */
	public function getTreeDepth(&$tree_depth = 0) {
		$tree_depth++;

		if ($this->getParent()) {
			$this->getParent()->getTreeDepth($tree_depth);
		}

		//echo($this->getCode()."|".$tree_depth."\n");

		return $tree_depth;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRouterIdParameter() {
		return '{' . $this->getIdParameter() . '}';
	}

	/**
	 * Scrive l'id come i+(x*tree_depth) 
	 * 
	 */
	public function getIdParameter() {
		$tr = $this->getTreeDepth();
		$id = str_pad('i', $tr + 1, 'x', STR_PAD_RIGHT);

		return $id;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function configureRoutes(RouteCollection $collection) {
		$collection->remove('batch');
		$collection->remove('export');
	}

	/**
	 * {@inheritdoc}
	 */
	public function generateObjectUrl($name, $object, array $parameters = array(), $absolute = false) {
		//$parameters[$this->getIdParameter()] = $this->getUrlsafeIdentifier($object);
		$parameters[$this->getIdParameter()] = $object->getId();
		
		if ($this->getParentAssociationMapping()) {
			$method = 'get' . ucfirst($this->getParentAssociationMapping());
			if (method_exists($object, $method)) {
				$parameters[substr($this->getIdParameter(), 0, -1)] = $object->$method()->getId();
			}
		}

		return $this->generateUrl($name, $parameters, $absolute);
	}

	/**
	 * {@inheritdoc}
	 */
	public function generateUrl($name, array $parameters = array(), $absolute = false) {

		$tr = $this->getTreeDepth();

		//if(stripos($name,'edit') !== false)echo(print_r($parameters,true).'|'.$this->getIdParameter());
		// riporto i parametri non gestiti

		if (!$this->getRequest()) {
			$this->request = $this->getRequestFromParents();
		}

		if ($this->request) {
			for ($i = 2; $i < $tr + 2; $i++) {
				$id = str_pad('i', $i, 'x', STR_PAD_RIGHT);
				if ($id !== $this->getIdParameter()) {
					if ($this->request->get($id)) {
						$parameters[$id] = $this->request->get($id);
					}
				}
			}
		}

		//if(stripos($name,'edit') !== false)exit(print_r($parameters,true).'|'.$this->getIdParameter());

		return $this->routeGenerator->generateUrl($this, $name, $parameters, $absolute);
	}

	/**
	 * 
	 * @return array
	 */
	public function getParentsForBreadCrumb() {

		$parents = array_reverse($this->explicit_parents);

		// elimino il primo che è la root
		array_shift($parents);

		return $parents;
	}

	/**
	 * Returns the current child admin instance
	 *
	 * @return \Sonata\AdminBundle\Admin\AdminInterface|null the current child admin instance
	 */
	public function getCurrentChildAdmin() {
		foreach ($this->children as $children) {
			if ($children->getCurrentChild()) {

				$sub = $children;

				if (count($children->getChildren())) {
					$sub = $children->getCurrentChildAdmin();
				}
				return $sub;
			}
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRequest() {

		if (!$this->request) {
			$childAdmin = $this->getCurrentChildAdmin();
			$this->request = ($childAdmin) ? $childAdmin->getRequestFromParents() : $this->getRequestFromParents();
		}

		// Cogitoweb: sometimes request can't be retrieved, so we bypass injection and get it straight from the service container
		/**
		 * @todo fix
		 */
		if (!$this->request) {
			$this->request = $this->getConfigurationPool()->getContainer()->get('request');
		}

		if (!$this->request) {
			throw new \RuntimeException('The Request object has not been set');
		}

		return $this->request;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRouteTemplate($route, $op, $idShow = null) {

		$depth = $this->getTreeDepth();
		$params = array();
		$tmp_param = $start_param = 'ix';

		for ($i = 0; $i < $depth; $i++) {
			if ($i == 0) {
				$params[$start_param] = $this->request->get($start_param);
			} else {
				$tmp_param .=  "x";
				$params[$tmp_param] = $this->request->get($tmp_param);
			}
		}

		if ($op == "show") {

			if (!$idShow) {
				throw new \Exception('missing mandatory parameter to compose show route');
			}

			$params[$tmp_param . 'x'] = $idShow;
		}

		$url = $this->routeGenerator->generate($route . "_" . $op, $params);
		return $url;
	}

	/**
	 * @param array $collection
	 * @param string $format
	 * @return 
	 */
	public static function getCollectionIdsStatic($collection, $format = null) {
		$ids = array();
		foreach ($collection as $i)
		{
			if(is_object($i)) {
				$ids[] = $i->getId();
			}
			else if(is_array($i) && isset($i['id'])) {
				$ids[] = $i['id'];
			}
		}

		switch ($format) {
			case self::POSTGRES_ARRAY_FORMAT:
				return sprintf("{%s}", implode(',', $ids));
			case self::PHP_ARRAY_FORMAT:

			default:
				return $ids;
		}
	}

	/**
	 * @param array $collection
	 * @param string $format
	 * @return 
	 */  
	public function getCollectionIds($collection, $format = null) {
		return self::getCollectionIdsStatic($collection, $format);
	}

	/**
	 * Inietta le proprietà aggiuntive se addQueryResults definita
	 * 
	 * @param integer $id
	 */
	public function getObject($id) {
		$object = parent::getObject($id);

		$funct = $this->addQueryResults();

		if($funct)
		{
			// chiamo la closure
			$res = $funct(array(array('id' => $object->getId())));

			if(count($res)) {

				$d = $res[0];

				// se match id 
				if(isset($d['id']) && method_exists($object, 'getId') && $d['id'] == $object->getId()) {

					foreach($d as $k => $v) {

						// provo a settare se esiste
						$m = Inflector::camelize('set_'.$k);

						if(method_exists($object, $m)) {
							$object->$m($v);
						}
					}
				}
			}
		}

		return $object;
	}
	
	/**
     * {@inheritdoc}
     */
    public function showIn($context)
    {
		// Cogitoweb: filter visibility in dashboard and menu for not child Admins
		if ($this->isChild()) return false;
        switch ($context) {
            case self::CONTEXT_DASHBOARD:
            case self::CONTEXT_MENU:
            default:
                return $this->isGranted($this->getPermissionsShow($context));
        }
    }
}