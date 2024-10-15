<?php

final class PhabricatorBadgesEditRecipientsController
  extends PhabricatorBadgesController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');
    $xactions = array();
    $errors = array();
    $e_recipient = true;

    $badge = id(new PhabricatorBadgesQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_EDIT,
          PhabricatorPolicyCapability::CAN_VIEW,
        ))
      ->executeOne();
    if (!$badge) {
      return new Aphront404Response();
    }

    $view_uri = $this->getApplicationURI('recipients/'.$badge->getID().'/');

    if ($request->isFormPost()) {
      $award_phids = array();

      $add_recipients = $request->getArr('phids');
      if ($add_recipients) {
        foreach ($add_recipients as $phid) {
          $award_phids[$phid] = $phid;
        }
      } else {
        $errors[] = pht('Recipient name is required.');
        $e_recipient = pht('Required');
      }

      if (!$errors) {
        $xactions[] = id(new PhabricatorBadgesTransaction())
          ->setTransactionType(
            PhabricatorBadgesBadgeAwardTransaction::TRANSACTIONTYPE)
          ->setNewValue($award_phids);

        $editor = id(new PhabricatorBadgesEditor())
          ->setActor($viewer)
          ->setContentSourceFromRequest($request)
          ->setContinueOnNoEffect(true)
          ->setContinueOnMissingFields(true)
          ->applyTransactions($badge, $xactions);

        return id(new AphrontRedirectResponse())
          ->setURI($view_uri);
      }
    }

    $form = new AphrontFormView();
    $form
      ->setUser($viewer)
      ->setFullWidth(true)
      ->appendControl(
        id(new AphrontFormTokenizerControl())
          ->setName('phids')
          ->setLabel(pht('Recipients'))
          ->setError($e_recipient)
          ->setDatasource(new PhabricatorPeopleDatasource()));

    $dialog = id(new AphrontDialogView())
      ->setUser($viewer)
      ->setTitle(pht('Add Recipients'))
      ->appendForm($form)
      ->addCancelButton($view_uri)
      ->addSubmitButton(pht('Add Recipients'));

    return $dialog;
  }

}
