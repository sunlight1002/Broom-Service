import React, { useRef, useState, useEffect } from 'react'
import axios from 'axios';
import { Link, useParams } from 'react-router-dom';
import ContractEng from '../../Components/contract/ContractEng'
import ContractHeb from '../../Components/contract/ContractHeb'
export default function WorkContract() {

    const param = useParams();
    const [lng, setLng] = useState([]);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        if (windowWidth < 767) {
            setMobileView(true)
        } else {
            setMobileView(false)
        }
    }, [windowWidth])


    const getContract = () => {
        axios
            .post(`/api/admin/get-contract/${param.id}`, {}, { headers })
            .then((res) => {
                console.log(res.data?.contract?.client?.lng);
                setLng(res.data?.contract?.client?.lng)
            })
    }
    useEffect(() => {
        getContract();
    }, [])

    const handleNextPrev = (e) => {
        window.scrollTo(0, 0);
        if (e.target.name === "prev") {
            setNextStep(prev => prev - 1);
        } else {
            setNextStep(prev => prev + 1);
        }

    }


    return (
        (lng == 'heb') ? <ContractHeb />: <ContractEng />
        // <ContractEng/>

    )

}
