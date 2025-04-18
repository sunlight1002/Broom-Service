import React, { useRef, useState, useEffect } from 'react'
import axios from 'axios';
import { Link, useParams } from 'react-router-dom';
import ContractEng from '../../Components/contract/ContractEng'
import ContractHeb from '../../Components/contract/ContractHeb'
import AdminNewContract from './AdminNewContract'

export default function WorkContract() {
    const param = useParams();
    const [lng, setLng] = useState([]);
    const [clientId, setClientId] = useState(null);
    const isEnabled = (process.env.MIX_ENABLE_NEW_CONTRACT === 'true');
    console.log(isEnabled);
    

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getContract = () => {
        axios
            .post(`/api/admin/get-contract/${param.id}`, {}, { headers })
            .then((res) => {
                setClientId(res.data?.contract?.client?.id)
                setLng(res.data?.contract?.client?.lng)
            })
    }
    useEffect(() => {
        getContract();
    }, [])

    console.log(clientId);

    return (
        <>
            {
                ([0].includes(clientId) || isEnabled) ? <AdminNewContract/> : ((lng == 'heb') ? <ContractHeb />: <ContractEng />)
            }
        </>
        // <ContractEng/>

    )

}
