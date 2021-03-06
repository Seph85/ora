<?php
namespace Accounting\View;

use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use Ora\ReadModel\Account;
use Ora\ReadModel\OrganizationAccount;
use Zend\Mvc\Controller\Plugin\Url;

class AccountsJsonModel extends StatementJsonModel
{
	public function serialize()
	{
		$resource = $this->getVariable('resource');
		if(is_array($resource)) {
			$representation['accounts'] = [];
			foreach ($resource as $account) {
				$representation['accounts'][] = $this->serializeOne($account);
			}
		} else {
			$representation = $this->serializeOne($resource);
		}
		return Json::encode($representation);
	}
	
	private function serializeOne(Account $account) {
		$rv = $this->serializeBalance($account);
		$rv['createdAt'] = date_format($account->getCreatedAt(), 'c');
		if($account instanceof OrganizationAccount) {
			$rv['organization'] = $account->getOrganization()->getName();
		}
		$rv = array_merge($rv, $this->serializeLinks($account));
		return $rv;
	}
	
	protected function serializeLinks($account) {
		$rv['_links']['self'] = $this->url->fromRoute('accounts', ['id' => $account->getId()]);
		if($this->isAllowed('statement', $account)) { 
			$rv['_links']['statement'] = $this->url->fromRoute('accounts', ['id' => $account->getId(), 'controller' => 'statement']);
		}
		if($this->isAllowed('deposit', $account)) {
			$rv['_links']['deposits'] = $this->url->fromRoute('accounts', ['id' => $account->getId(), 'controller' => 'deposits']);
		}
		return $rv;
	}
	
}