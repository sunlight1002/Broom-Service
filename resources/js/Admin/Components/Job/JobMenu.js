import { useRef, useState, memo } from "react";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import JobModal from "./JobModal";
import { useParams } from "react-router-dom";

const jobActions = [
    {
        key: "edit",
        label: "Edit",
    },
    {
        key: "delete",
        label: "Delete",
    },
];
const initialValue = {
    service: "",
    name: "",
    type: "fixed",
    freq_name: "",
    frequency: "",
    fixed_price: "",
    jobHours: "",
    rateperhour: "",
    other_title: "",
    totalamount: "",
    template: "",
    cycle: "",
    period: "",
    address: "",
    start_date: "",
    weekdays: [],
    weekday_occurrence: "1",
    weekday: "sunday",
    month_occurrence: 1,
    month_date: 1,
    monthday_selection_type: "weekday",
};
const JobMenu = memo(function JobMenu({
    addresses,
    AllServices,
    AllFreq,
    formValues,
    handleSaveJobForm,
    handleRemoveFormFields,
}) {
    let isAdd = useRef(true);
    let indexRef = useRef();
    const [isOpen, setIsOpen] = useState(false);
    const [tmpFormValues, setTmpFormValues] = useState(initialValue);
    let param = useParams();
    const handleAddJob = () => {
        if (!addresses.length) {
            alert("Please add property address");
            return;
        }
        setTmpFormValues(initialValue);
        isAdd.current = true;
        setIsOpen(true);
    };

    return (
        <div>
            <div className="text-right" style={{ marginBottom: "5px" }}>
                <button
                    type="button"
                    onClick={handleAddJob}
                    className="btn btn-success"
                >
                    + Add Job
                </button>
            </div>
            <div className="table-responsive">
                {formValues.length > 0 ? (
                    <Table className="table table-bordered">
                        <Thead>
                            <Tr>
                                <Th>Address</Th>
                                <Th>Worker Name</Th>
                                <Th>Worker Availability</Th>
                                <Th>Service</Th>
                                <Th>Type</Th>
                                <Th>Job Hours</Th>
                                <Th>Price</Th>
                                <Th>Frequency</Th>
                                <Th>Actions</Th>
                            </Tr>
                        </Thead>
                        <Tbody>
                            {formValues.length > 0 &&
                                formValues.map((item, adIndex) => {
                                    return (
                                        <Tr key={adIndex}>
                                            <Td>
                                                {param.id
                                                    ? addresses.filter(
                                                          (a) =>
                                                              a.id ==
                                                              item.address
                                                      )[0]?.address_name
                                                    : addresses[item.address]
                                                          ?.address_name}
                                            </Td>
                                            <Td>{item.woker_name}</Td>
                                            <Td>{item.shift}</Td>
                                            <Td>{item.name}</Td>
                                            <Td>{item.type}</Td>
                                            <Td>{item.jobHours}</Td>
                                            <Td>
                                                {/* {item.type === "hourly"
                                                    ? item.jobHours *
                                                      item.rateperhour
                                                    : item.fixed_price} */}
                                                    {
                                                    item.type === "hourly"
                                                        ? item.jobHours * item.rateperhour
                                                        : item.type === "squaremeter"
                                                        ? item.ratepersquaremeter * item.totalsquaremeter
                                                        : item.fixed_price
                                                    }
                                            </Td>
                                            <Td>{item.freq_name}</Td>
                                            <Td>
                                                <div className="action-dropdown dropdown">
                                                    <button
                                                        type="button"
                                                        className="btn btn-default dropdown-toggle"
                                                        data-toggle="dropdown"
                                                    >
                                                        <i className="fa fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <div className="dropdown-menu">
                                                        {jobActions.map(
                                                            (menu, i) => {
                                                                return (
                                                                    <button
                                                                        type="button"
                                                                        className="dropdown-item"
                                                                        key={
                                                                            menu.key
                                                                        }
                                                                        onClick={(
                                                                            e
                                                                        ) => {
                                                                            e.preventDefault();
                                                                            if (
                                                                                menu.key ===
                                                                                "edit"
                                                                            ) {
                                                                                indexRef.current =
                                                                                    adIndex;
                                                                                isAdd.current = false;
                                                                                setTmpFormValues(
                                                                                    item
                                                                                );
                                                                                setIsOpen(
                                                                                    true
                                                                                );
                                                                            } else {
                                                                                handleRemoveFormFields(
                                                                                    adIndex
                                                                                );
                                                                            }
                                                                        }}
                                                                    >
                                                                        {
                                                                            menu.label
                                                                        }
                                                                    </button>
                                                                );
                                                            }
                                                        )}
                                                    </div>
                                                </div>
                                            </Td>
                                        </Tr>
                                    );
                                })}
                        </Tbody>
                    </Table>
                ) : (
                    <p className="text-center mt-5">{"Jobs not found!"}</p>
                )}
            </div>
            {isOpen && (
                <JobModal
                    setIsOpen={setIsOpen}
                    isOpen={isOpen}
                    addresses={addresses}
                    AllServices={AllServices}
                    AllFreq={AllFreq}
                    tmpFormValues={tmpFormValues}
                    handleTmpValue={setTmpFormValues}
                    handleSaveJobForm={handleSaveJobForm}
                    isAdd={isAdd.current}
                    index={indexRef.current}
                />
            )}
        </div>
    );
});
export default JobMenu;
