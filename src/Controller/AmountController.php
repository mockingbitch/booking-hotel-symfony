<?php

namespace App\Controller;

use App\Entity\Amount;
use App\Repository\AmountRepository;
use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AmountController extends AbstractController
{
    /**
     * @var AmountRepository
     */
    private $amountRepository;

    /**
     * @var RoomRepository
     */
    private $roomRepository;

    /**
     * @param AmountRepository $amountRepository
     * @param RoomRepository $roomRepository
     */
    public function __construct(
        AmountRepository $amountRepository,
        RoomRepository $roomRepository
    ) {
        $this->amountRepository = $amountRepository;
        $this->roomRepository = $roomRepository;
    }

    /**
     * @return Response
     */
    public function list() : Response
    {
        $amount = $this->amountRepository->findAll();

        return $amount ?
            $this->json(['amount'=>$amount], 200) :
            $this->json(['msg'=>'Empty amount!'], 200);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function create(Request $request) : Response
    {
        $request = json_decode($request->getContent(), true);

        if (!isset($request['start_date'],$request['end_date'],$request['room'],$request['price'])) {
            return $this->json(['msg'=>'Expected fields: room, price, start_date, end_date'], 200);
        }

        $room = $this->roomRepository->find($request['room']);

        if (!isset($room)) {
            return $this->json(['msg'=>'Could not find room!'], 404);
        }

        $date = $this->amountRepository->dateRange($request['start_date'], $request['end_date']);

        foreach ($date as $date) {
            $amounts = $this->amountRepository->findByDay($request['room'], $date);

            if ($amounts != null) {
                return $this->json(['msg' => 'Existed price on'.$date.'!'], 200);
            }

            $amount = new Amount();
            $amount->setRoom($room);
            $amount->setDay(\DateTime::createFromFormat('Y-m-d', $date));
            $amount->setPrice($request['price']);
            $this->getDoctrine()->getManager()->persist($amount);
        }

        if (isset($request['special_date'])) {
            $amount = new Amount();
            $amount->setRoom($room);
            $amount->setDay(\DateTime::createFromFormat('Y-m-d', $request['special_date']));
            $amount->setPrice($request['price']);
            $this->getDoctrine()->getManager()->persist($amount);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->json(['amount' => $amount], 201);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function update(Request $request) : Response
    {
        $request = json_decode($request->getContent(), true);

        if (!isset($request['start_date'],$request['end_date'],$request['room'],$request['price'])) {
            return $this->json(['msg'=>'Expected fields: room, price, start_date, end_date'], 200);
        }

        $date = $this->amountRepository->dateRange($request['start_date'], $request['end_date']);

        if (isset($request['special_date'])) {
            $amount = $this->amountRepository->findByDay($request['room'], $request['special_date']);
            $amount->setDay(\DateTime::createFromFormat('Y-m-d', $request['special_date']));
            $amount->setPrice($request['price']);
            $this->getDoctrine()->getManager()->persist($amount);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->json(['amount' => $amount], 200);
    }
}
