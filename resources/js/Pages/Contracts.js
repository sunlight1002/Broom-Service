import React, { useEffect , useState} from 'react'
import { useParams } from 'react-router-dom';
import NewContract from './NewContract';
import WorkContract from './WorkContract';

const Contracts = () => {
    const param = useParams();
    const [clientId, setClientId] = useState(null);
    const isEnabled = (process.env.MIX_ENABLE_NEW_CONTRACT === 'true');

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };


    const getOffer = () => {
        axios
            .post(`/api/client/contracts/${param.id}`, { headers })

            .then((res) => {
                setClientId(res.data?.offer?.client?.id)
            })
    }

    useEffect(() => {
        getOffer();
    }, [])

    return (
        <div>{
            ([0].includes(clientId) || isEnabled ) ? <NewContract/> : <WorkContract/>
        }</div>
    )
}

export default Contracts