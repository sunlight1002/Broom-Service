import { memo, useState, useEffect } from "react";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { Tooltip } from "react-tooltip";

import AddCommentModal from "../Modals/AddCommentModal";
import CommentsModal from "../Modals/CommentsModal";

const PropertyAddressTable = memo(function PropertyAddressTable({ clientId }) {
    const [address, setAddress] = useState([]);
    const [isOpenAddComment, setIsOpenAddComment] = useState(false);
    const [isOpenCommentList, setIsOpenCommentList] = useState(false);
    const [selectedPropertyID, setSelectedPropertyID] = useState(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getPropertyAddress = () => {
        axios
            .get(`/api/admin/clients/${parseInt(clientId)}/edit`, {
                headers,
            })
            .then((res) => {
                const { client } = res.data;
                if (
                    client.property_addresses &&
                    client.property_addresses.length > 0
                ) {
                    setAddress(res.data.client.property_addresses);
                }
            });
    };

    const handleAddComment = (_propertyID) => {
        setSelectedPropertyID(_propertyID);
        setIsOpenAddComment(true);
    };

    const handleShowComments = (_propertyID) => {
        setSelectedPropertyID(_propertyID);
        setIsOpenCommentList(true);
    };

    useEffect(() => {
        getPropertyAddress();
    }, [clientId]);

    return (
        <div>
            <div className="card">
                <div className="card-body">
                    <div className="boxPanel">
                        {address.length > 0 ? (
                            <Table className="table table-bordered">
                                <Thead>
                                    <Tr>
                                        <Th>Name</Th>
                                        <Th>Address</Th>
                                        <Th>Zipcode</Th>
                                        <Th>Action</Th>
                                    </Tr>
                                </Thead>
                                <Tbody>
                                    {address &&
                                        address.map((item, index) => {
                                            return (
                                                <Tr key={index}>
                                                    <Td>
                                                        {item.address_name
                                                            ? item.address_name
                                                            : "NA"}{" "}
                                                    </Td>
                                                    <Td>
                                                        {item.geo_address
                                                            ? item.geo_address
                                                            : "NA"}{" "}
                                                    </Td>
                                                    <Td>
                                                        {item.zipcode
                                                            ? item.zipcode
                                                            : "NA"}
                                                    </Td>
                                                    <Td>
                                                        <button
                                                            onClick={() => {
                                                                handleAddComment(
                                                                    item.id
                                                                );
                                                            }}
                                                            data-tooltip-id="address-tooltip"
                                                            data-tooltip-content="Add Comment"
                                                        >
                                                            <i className="fa fa-comment-medical"></i>
                                                        </button>

                                                        <button
                                                            className="ml-1"
                                                            onClick={() => {
                                                                handleShowComments(
                                                                    item.id
                                                                );
                                                            }}
                                                            data-tooltip-id="address-tooltip"
                                                            data-tooltip-content="Comment List"
                                                        >
                                                            <i className="fa fa-comments"></i>
                                                        </button>
                                                    </Td>
                                                </Tr>
                                            );
                                        })}
                                </Tbody>
                            </Table>
                        ) : (
                            <p className="text-center mt-5">
                                {"Address not found!"}
                            </p>
                        )}
                    </div>

                    {isOpenAddComment && selectedPropertyID && (
                        <AddCommentModal
                            relationID={selectedPropertyID}
                            routeType="property-addresses"
                            isOpen={isOpenAddComment}
                            setIsOpen={setIsOpenAddComment}
                            onSuccess={() => {}}
                        />
                    )}

                    {isOpenCommentList && selectedPropertyID && (
                        <CommentsModal
                            relationID={selectedPropertyID}
                            routeType="property-addresses"
                            isOpen={isOpenCommentList}
                            setIsOpen={setIsOpenCommentList}
                            canAddComment={false}
                            size="lg"
                        />
                    )}
                </div>
            </div>

            <Tooltip id="address-tooltip" />
        </div>
    );
});

export default PropertyAddressTable;
